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
     *
     * @param bool $evaluable si es contenido evaluable (cuenta para la nota y no se puede reintentar)
     */
    public function storeBlock(int $unitId, array $uploaded, string $title, bool $evaluable = false): ContentBlock
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

            // 2c) Guardar en settings si es evaluable y qué tipo de contenido es
            //     (mainLibrary), para distinguir ensayos (H5P.Essay) de los
            //     contenidos auto-calificables.
            if ($evaluable) {
                $manifestPath = $root . DIRECTORY_SEPARATOR . 'h5p.json';
                $mainLibrary = '';
                if (is_file($manifestPath)) {
                    $manifest = json_decode((string) file_get_contents($manifestPath), true);
                    $mainLibrary = is_array($manifest) ? (string) ($manifest['mainLibrary'] ?? '') : '';
                }
                Connection::run(
                    'UPDATE content_blocks SET settings = :s WHERE id = :id',
                    [':s' => json_encode(['evaluable' => true, 'mainLibrary' => $mainLibrary]), ':id' => $blockId]
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
     * Registra el envío de un bloque H5P evaluable (un solo intento).
     *
     * No guarda la nota todavía: crea una entrega en estado PENDING. Si el
     * contenido reportó puntaje (opción múltiple, quiz, etc.) se auto-califica;
     * si no (p. ej. H5P.Essay) queda pendiente de corrección manual. El
     * estudiante no ve el resultado hasta que el docente libere la evaluación.
     *
     * @return array{submitted: bool}
     */
    public function recordResult(int $studentId, int $blockId, float $score, float $maxScore): array
    {
        $block = $this->findBlock($blockId);

        if (! $this->isEvaluable($block)) {
            // Contenido práctico (no evaluable): solo se marca el progreso.
            $this->progress->markBlockCompleted($studentId, $blockId);
            return ['submitted' => false];
        }

        $existing = Connection::fetch(
            'SELECT id FROM h5p_submissions WHERE student_id = :s AND block_id = :b',
            [':s' => $studentId, ':b' => $blockId]
        );
        if ($existing !== null) {
            throw HttpException::conflict('Ya realizó esta evaluación.');
        }

        $autoGraded = $maxScore > 0;
        $percentage = $autoGraded ? round(($score / $maxScore) * 100, 2) : null;

        // Para contenidos sin puntaje (ensayos), copia el texto capturado.
        $answerText = null;
        if (! $autoGraded) {
            $state = Connection::fetch(
                'SELECT answer_text FROM h5p_states WHERE student_id = :s AND block_id = :b',
                [':s' => $studentId, ':b' => $blockId]
            );
            $answerText = $state !== null ? $state['answer_text'] : null;
        }

        Connection::run(
            'INSERT INTO h5p_submissions (student_id, block_id, score, max_score, percentage, auto_graded, answer_text, status)
             VALUES (:s, :b, :score, :max, :pct, :auto, :answer, :status)',
            [
                ':s' => $studentId,
                ':b' => $blockId,
                ':score' => $autoGraded ? $score : null,
                ':max' => $autoGraded ? $maxScore : null,
                ':pct' => $percentage,
                ':auto' => $autoGraded ? 1 : 0,
                ':answer' => $answerText,
                ':status' => 'PENDING',
            ]
        );

        $this->progress->markBlockCompleted($studentId, $blockId);

        return ['submitted' => true];
    }

    /** Indica si un bloque H5P está marcado como evaluable. */
    public function isEvaluable(ContentBlock $block): bool
    {
        $settings = $block->settingsArray();

        return ($settings['evaluable'] ?? false) === true;
    }

    /** Entrega (si existe) de un estudiante para un bloque. */
    public function submissionFor(int $studentId, int $blockId): ?array
    {
        return Connection::fetch(
            'SELECT * FROM h5p_submissions WHERE student_id = :s AND block_id = :b',
            [':s' => $studentId, ':b' => $blockId]
        );
    }

    /**
     * Contenidos H5P evaluables de un Saber (a través de sus unidades).
     */
    public function evaluableBlocksForSaber(int $saberId): array
    {
        $rows = Connection::fetchAll(
            'SELECT cb.id, cb.title, cb.settings, u.id AS unit_id, u.title AS unit_title
             FROM content_blocks cb
             JOIN units u ON u.id = cb.unit_id
             WHERE cb.type = :t AND u.saber_id = :saber
             ORDER BY u.position, cb.position',
            [':t' => 'H5P', ':saber' => $saberId]
        );

        return array_values(array_filter($rows, function (array $row): bool {
            $settings = is_string($row['settings'] ?? null) ? json_decode($row['settings'], true) : null;
            return is_array($settings) && ($settings['evaluable'] ?? false) === true;
        }));
    }

    /** Entregas de un bloque (para la pantalla de corrección del docente). */
    public function submissionsForBlock(int $blockId): array
    {
        return Connection::fetchAll(
            'SELECT hs.*, st.state AS state_json, st.answer_text AS state_answer,
                    u.first_name, u.last_name, u.email
             FROM h5p_submissions hs
             JOIN users u ON u.id = hs.student_id
             LEFT JOIN h5p_states st ON st.student_id = hs.student_id AND st.block_id = hs.block_id
             WHERE hs.block_id = :b
             ORDER BY hs.submitted_at ASC',
            [':b' => $blockId]
        );
    }

    /**
     * Guarda la nota manual de una entrega (ensayo). El docente asigna un puntaje
     * sobre max_score (por defecto 20). No libera todavía.
     */
    public function gradeSubmission(int $submissionId, float $score, float $maxScore, int $graderId): void
    {
        $sub = Connection::fetch('SELECT * FROM h5p_submissions WHERE id = :id', [':id' => $submissionId]);
        if ($sub === null) {
            throw HttpException::notFound('Entrega no encontrada.');
        }
        if ($maxScore <= 0) {
            $maxScore = 20.0;
        }
        if ($score < 0) {
            $score = 0.0;
        }
        if ($score > $maxScore) {
            $score = $maxScore;
        }
        $percentage = round(($score / $maxScore) * 100, 2);

        Connection::run(
            'UPDATE h5p_submissions
             SET score = :score, max_score = :max, percentage = :pct, auto_graded = FALSE, graded_by = :gb, graded_at = :at
             WHERE id = :id',
            [':score' => $score, ':max' => $maxScore, ':pct' => $percentage, ':gb' => $graderId, ':at' => date('Y-m-d H:i:s'), ':id' => $submissionId]
        );
    }

    /**
     * Libera los resultados de un bloque evaluable (todos los estudiantes).
     * Requiere que todas las entregas tengan nota (los ensayos deben corregirse
     * antes). Registra la nota en `grades` y recalcula la compuesta.
     *
     * @return int número de entregas liberadas
     */
    public function releaseBlock(int $blockId): int
    {
        $block = $this->findBlock($blockId);
        $unit = Connection::fetch('SELECT saber_id FROM units WHERE id = :id', [':id' => $block->unit_id]);
        if ($unit === null) {
            throw HttpException::notFound('Unidad no encontrada.');
        }

        $pending = $this->submissionsForBlock($blockId);
        foreach ($pending as $sub) {
            if ($sub['percentage'] === null || $sub['score'] === null) {
                throw HttpException::unprocessable(
                    'Hay entregas sin calificar (ensayos). Corrija todas las notas antes de liberar.'
                );
            }
        }

        $released = 0;
        foreach ($pending as $sub) {
            if ($sub['status'] === 'RELEASED') {
                continue;
            }
            $studentId = (int) $sub['student_id'];
            $assignments = $this->progress->assignmentsForSaber($studentId, (int) $unit['saber_id']);
            foreach ($assignments as $assignmentId) {
                $this->grades->recordH5p(
                    $assignmentId,
                    $studentId,
                    $blockId,
                    (float) $sub['score'],
                    (float) $sub['percentage']
                );
                $this->grades->recordComposite($assignmentId, $studentId);
            }
            Connection::run(
                'UPDATE h5p_submissions SET status = :st, released_at = :at WHERE id = :id',
                [':st' => 'RELEASED', ':at' => date('Y-m-d H:i:s'), ':id' => (int) $sub['id']]
            );
            $released++;
        }

        return $released;
    }

    /**
     * Guarda el estado de contenido (contentUserData) del runtime H5P. Se usa
     * para capturar la respuesta de los ensayos (H5P.Essay).
     */
    public function saveState(int $studentId, int $blockId, array $data): void
    {
        $this->findBlock($blockId);
        $state = json_encode($data, JSON_UNESCAPED_UNICODE);

        // Extrae el texto del ensayo si viene en el estado.
        $answer = $data['answer'] ?? null;
        $answerText = is_string($answer) ? $answer : null;

        Connection::run(
            'INSERT INTO h5p_states (student_id, block_id, state, answer_text, updated_at)
             VALUES (:s, :b, :state, :answer, :at)'
            . Connection::onConflict('student_id, block_id')
            . 'state = :state, answer_text = :answer, updated_at = :at',
            [
                ':s' => $studentId,
                ':b' => $blockId,
                ':state' => $state,
                ':answer' => $answerText,
                ':at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** Devuelve el estado guardado de un estudiante en un bloque. */
    public function loadState(int $studentId, int $blockId): ?array
    {
        $row = Connection::fetch(
            'SELECT state FROM h5p_states WHERE student_id = :s AND block_id = :b',
            [':s' => $studentId, ':b' => $blockId]
        );
        if ($row === null) {
            return null;
        }
        $decoded = json_decode((string) $row['state'], true);
        return is_array($decoded) ? $decoded : null;
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
