<?php

declare(strict_types=1);

namespace App\Modules\H5p\Services;

use App\Core\Exceptions\HttpException;
use App\Core\Storage\S3Storage;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Content\Repositories\ContentBlockRepository;

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

    public function __construct()
    {
        $this->blocks = new ContentBlockRepository();
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

        // Se extrae a un directorio temporal y luego cada archivo se sube a R2.
        // El temporal es efímero y se limpia al terminar; R2 queda como fuente única.
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'h5p_' . bin2hex(random_bytes(8));
        if (! mkdir($tempDir, 0775, true) && ! is_dir($tempDir)) {
            throw new HttpException(500, 'No se pudo preparar el directorio temporal para el contenido H5P.');
        }

        try {
            $phar = new \PharData($uploaded['tmp_name'], 0, null, \Phar::ZIP);
            $phar->extractTo($tempDir, null, true);

            $hasManifest = false;
            foreach ($this->listFiles($tempDir) as $relative) {
                if ($relative === 'h5p.json') {
                    $hasManifest = true;
                }
                $storage->put(
                    $prefix . '/' . $relative,
                    $tempDir . DIRECTORY_SEPARATOR . $relative,
                    self::mimeFor($relative)
                );
            }

            if (! $hasManifest) {
                throw HttpException::unprocessable('El paquete .h5p no contiene h5p.json (archivo inválido).');
            }
        } catch (HttpException $e) {
            $storage->deletePrefix($prefix);
            $this->blocks->delete($blockId);
            throw $e;
        } catch (\Throwable $e) {
            $storage->deletePrefix($prefix);
            $this->blocks->delete($blockId);
            throw HttpException::unprocessable('El archivo .h5p no es un paquete válido.');
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
