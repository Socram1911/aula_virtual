<?php

declare(strict_types=1);

namespace App\Modules\H5p\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Content\Repositories\ContentBlockRepository;

/**
 * Reproducción nativa de contenido interactivo H5P.
 *
 * El docente sube un paquete .h5p (creado con Lumi u obtenido de H5P.org);
 * se extrae a storage/h5p/{blockId}/ y se reproduce con h5p-standalone.
 */
final class H5pService
{
    private const MAX_SIZE = 100 * 1024 * 1024; // 100 MB

    private ContentBlockRepository $blocks;

    public function __construct()
    {
        $this->blocks = new ContentBlockRepository();
    }

    /** Crea un bloque H5P y extrae el paquete. Devuelve el bloque creado. */
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

        $dir = $this->directory($blockId);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new HttpException(500, 'No se pudo crear el directorio para el contenido H5P.');
        }

        try {
            $phar = new \PharData($uploaded['tmp_name']);
            $phar->extractTo($dir, null, true);
        } catch (\Throwable $e) {
            $this->cleanup($blockId);
            throw HttpException::unprocessable('El archivo .h5p no es un paquete válido.');
        }

        // Validación mínima: debe existir h5p.json.
        if (! is_file($dir . DIRECTORY_SEPARATOR . 'h5p.json')) {
            $this->cleanup($blockId);
            throw HttpException::unprocessable('El paquete .h5p no contiene h5p.json (archivo inválido).');
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

    /** Directorio absoluto donde está extraído el paquete H5P. */
    public function directory(int $blockId): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'h5p' . DIRECTORY_SEPARATOR . $blockId;
    }

    /** Resuelve una ruta relativa dentro del directorio H5P de forma segura. */
    public function resolve(int $blockId, string $relative): ?string
    {
        $base = realpath($this->directory($blockId));
        if ($base === false) {
            return null;
        }
        $target = realpath($base . DIRECTORY_SEPARATOR . $relative);
        if ($target === false || ! str_starts_with($target, $base . DIRECTORY_SEPARATOR) && $target !== $base) {
            return null;
        }
        return $target;
    }

    private function cleanup(int $blockId): void
    {
        $dir = $this->directory($blockId);
        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }
        $this->blocks->delete($blockId);
    }

    private function deleteDirectory(string $dir): void
    {
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
