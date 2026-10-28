<?php

declare(strict_types=1);

namespace App\Modules\H5p\Services;

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Core\Storage\S3Storage;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Content\Repositories\ContentBlockRepository;
use App\Modules\Grades\Services\GradeService;
use App\Modules\Progress\Services\ProgressService;

/**
 * Reproducción nativa de contenido interactivo H5P.
 *
 * El docente sube un paquete .h5p (creado con Lumi u obtenido de H5P.org);
 * cada archivo del paquete se guarda en R2 bajo h5p/{blockId}/ y se reproduce
 * con h5p-standalone.
 */
final class H5pService
{
    private const MAX_SIZE = 100 * 1024 * 1024; // 100 MB

    private ContentBlockRepository $blocks;
    private ProgressService $progress;
    private GradeService $grades;

    public function __construct()
    {
        $this->blocks = new ContentBlockRepository();
        $this->progress = new ProgressService();
        $this->grades = new GradeService();
    }

    /**
     * Crea un bloque H5P y sube cada archivo del paquete a R2
     * (clave h5p/{blockId}/{ruta}). Devuelve el bloque creado.
     */
    public function storeBlock(int $unitId, array $uploaded, string $title): ContentBlock
    {
        if (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw HttpException::unprocessable('Error al subir el archivo H5P.');
        }
        if ($uploaded['size'] > self::MAX_SIZE) {
            throw HttpException::unprocessable('El paquete H5P supera el tamaño máximo (100 MB).');
        }
        $originalName = basename((string) ($uploaded['name'] ?? 'contenido.h5p'));
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'h5p') {
            throw HttpException::unprocessable('El archivo debe tener extensión .h5p.');
        }

        $position = $this->blocks->nextPosition($unitId);
        $blockId = $this->blocks->create([
            ':unit_id' => $unitId,
            ':type' => 'H5P',
            ':position' => $position,
            ':title' => $title !== '' ? $title : $originalName,
            ':content' => $originalName,
            ':settings' => null,
            ':status' => 'ACTIVE',
        ]);

        $storage = new S3Storage();
        $prefix = 'h5p/' . $blockId;

        // Se extrae a un directorio temporal, se valida y luego cada archivo se
        // sube a R2. El temporal es efímero y se limpia al terminar; R2 queda
        // como fuente única.
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'h5p_' . bin2hex(random_bytes(8));
        if (! mkdir($tempDir, 0775, true) && ! is_dir($tempDir)) {
            $this->blocks->delete($blockId);
            throw new HttpException(500, 'No se pudo preparar el directorio temporal para el contenido H5P.');
        }

        try {
            // 1) Extraer el paquete (valida que sea un ZIP válido).
            //    Nota: el tmp_name de la subida no tiene extensión .zip, por lo
            //    que PharData no puede abrirlo directamente; se copia a un
            //    archivo con extensión (o se usa ZipArchive si está disponible).
            try {
                $this->extractPackage($uploaded['tmp_name'], $tempDir);
            } catch (HttpException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $this->blocks->delete($blockId);
                throw HttpException::unprocessable('El archivo .h5p no es un paquete válido.');
            }

            // 2) Localizar la raíz del contenido (el directorio que contiene
            //    h5p.json). Algunos paquetes .h5p llevan una carpeta superior;
            //    se aplanan para que el reproductor encuentre h5p.json en la base.
            $root = $this->findContentRoot($tempDir);
            if ($root === null) {
                $this->blocks->delete($blockId);
                throw HttpException::unprocessable('El paquete .h5p no contiene h5p.json (archivo inválido).');
            }

            // 2b) Validar que el paquete sea autocontenido: debe incluir todas
            //     las librerías declaradas en preloadedDependencies. Si no, el
            //     reproductor no podrá cargarlo (error 404 en library.json).
            $files = $this->listFiles($root);
            $missing = $this->missingLibraries($root, $files);
            if ($missing !== []) {
                $this->blocks->delete($blockId);
                throw HttpException::unprocessable(
                    'El paquete .h5p no incluye las librerías: ' . implode(', ', $missing)
                    . '. Regénéralo incluyendo las librerías (exportación completa).'
                );
            }

            // 3) Subir cada archivo a R2 con claves relativas a la raíz.
            try {
                foreach ($files as $relative) {
                    $storage->put(
                        $prefix . '/' . $relative,
                        $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative),
                        self::mimeFor($relative)
                    );
                }
            } catch (\Throwable $e) {
                error_log('No se pudo subir el contenido H5P a R2: ' . $e->getMessage());
                $storage->deletePrefix($prefix);
                $this->blocks->delete($blockId);
                throw new HttpException(500, 'No se pudo almacenar el contenido H5P.');
            }
        } finally {
            $this->deleteDirectory($tempDir);
        }

        return $this->blocks->find($blockId) ?? new ContentBlock();
    }

    public function findBlock(int $blockId): ContentBlock
    {
        $block = $this->blocks->find($blockId);
        if ($block === null || $block->type !== 'H5P') {
            throw HttpException::notFound('Contenido H5P no encontrado.');
        }
        return $block;
    }

    /**
     * Registra el resultado de un bloque H5P: si hay puntaje, guarda la
     * calificación (fuente H5P) en cada asignación del estudiante para el Saber
     * de la unidad y recalcula la compuesta. En todos los casos (el runtime solo
     * notifica al completar) marca el progreso del bloque como completado.
     *
     * @return array{percentage: float, graded: bool, assignments: int}
     */
    public function recordResult(int $studentId, int $blockId, float $score, float $maxScore): array
    {
        $block = $this->findBlock($blockId);

        $unit = Connection::fetch('SELECT saber_id FROM units WHERE id = :id', [':id' => $block->unit_id]);
        if ($unit === null) {
            throw HttpException::notFound('Unidad no encontrada.');
        }

        $graded = $maxScore > 0;
        $percentage = $graded ? round(($score / $maxScore) * 100, 2) : 0.0;

        $assignments = $this->progress->assignmentsForSaber($studentId, (int) $unit['saber_id']);

        foreach ($assignments as $assignmentId) {
            if ($graded) {
                $this->grades->recordH5p($assignmentId, $studentId, $blockId, $score, $percentage);
                $this->grades->recordComposite($assignmentId, $studentId);
            }
        }

        $this->progress->markBlockCompleted($studentId, $blockId);

        return [
            'percentage' => $percentage,
            'graded' => $graded,
            'assignments' => count($assignments),
        ];
    }

    /** MIME a partir de la extensión del archivo del paquete H5P. */
    public static function mimeFor(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'json' => 'application/json',
            'js' => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            default => 'application/octet-stream',
        };
    }

    /**
     * Valida y normaliza una ruta relativa solicitada vía /h5p/{id}/{path}.
     * Devuelve null si contiene segmentos peligrosos ("..", ".", vacíos).
     */
    public static function normalizePath(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }
        return $path;
    }

    /** Lista recursivamente los archivos de un directorio (rutas relativas con '/'). */
    private function listFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        }
        return $files;
    }

    /**
     * Localiza el directorio raíz del contenido H5P: el que contiene h5p.json.
     * Devuelve null si no existe h5p.json. Si h5p.json está bajo una única
     * carpeta superior, devuelve esa carpeta (aplana la estructura).
     */
    private function findContentRoot(string $dir): ?string
    {
        $files = $this->listFiles($dir);
        $relative = null;
        foreach ($files as $f) {
            if (basename($f) === 'h5p.json') {
                $relative = $f;
                break;
            }
        }
        if ($relative === null) {
            return null;
        }
        if ($relative === 'h5p.json') {
            return $dir;
        }
        // h5p.json bajo una única carpeta: se aplana esa carpeta.
        $parts = explode('/', $relative);
        if (count($parts) === 2) {
            return $dir . DIRECTORY_SEPARATOR . $parts[0];
        }
        return null;
    }

    /**
     * Devuelve la lista de librerías declaradas en h5p.json que NO están
     * presentes en el paquete (como "H5P.QuestionSet-1.20").
     *
     * @param string[] $files rutas relativas de los archivos extraídos
     * @return string[]
     */
    private function missingLibraries(string $root, array $files): array
    {
        $manifestPath = $root . DIRECTORY_SEPARATOR . 'h5p.json';
        $json = is_file($manifestPath) ? file_get_contents($manifestPath) : false;
        $manifest = $json === false ? null : json_decode($json, true);
        if (! is_array($manifest)) {
            return [];
        }

        $missing = [];
        foreach (($manifest['preloadedDependencies'] ?? []) as $dep) {
            if (! is_array($dep)) {
                continue;
            }
            $folder = trim((string) ($dep['machineName'] ?? ''));
            $major = (int) ($dep['majorVersion'] ?? 0);
            $minor = (int) ($dep['minorVersion'] ?? 0);
            if ($folder === '') {
                continue;
            }
            $folderWithVersion = $folder . '-' . $major . '.' . $minor;

            $found = false;
            foreach ($files as $file) {
                if (str_starts_with($file, $folderWithVersion . '/')
                    || str_starts_with($file, $folder . '/')) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $missing[] = $folderWithVersion;
            }
        }

        return $missing;
    }

    /**
     * Extrae el contenido de un paquete .h5p (ZIP) al directorio indicado.
     *
     * Prefiere ZipArchive (más estándar); si no está disponible, copia el
     * archivo temporal a un nombre .zip y usa PharData (que requiere extensión
     * para detectar el formato).
     */
    private function extractPackage(string $source, string $destDir): void
    {
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            $res = $zip->open($source);
            if ($res !== true) {
                throw new \RuntimeException("ZipArchive::open falló con código {$res}");
            }
            if (! $zip->extractTo($destDir)) {
                $zip->close();
                throw new \RuntimeException('ZipArchive::extractTo falló');
            }
            $zip->close();
            return;
        }

        // Fallback con PharData: requiere un archivo con extensión .zip.
        $zipPath = $destDir . DIRECTORY_SEPARATOR . 'package.zip';
        if (! copy($source, $zipPath)) {
            throw new \RuntimeException('No se pudo copiar el archivo temporal del paquete.');
        }
        try {
            $phar = new \PharData($zipPath);
            $phar->extractTo($destDir, null, true);
        } finally {
            @unlink($zipPath);
        }
    }

    /** Elimina un directorio y su contenido de forma recursiva. */
    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
