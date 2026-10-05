<?php

declare(strict_types=1);

namespace App\Modules\Files\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\Files\Models\File;
use App\Modules\Files\Repositories\FileRepository;

final class FileStorageService
{
    private const MAX_SIZE = 20 * 1024 * 1024; // 20 MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    private FileRepository $repository;

    public function __construct()
    {
        $this->repository = new FileRepository();
    }

    public function find(int $id): File
    {
        $file = $this->repository->find($id);
        if ($file === null) {
            throw HttpException::notFound('Archivo no encontrado.');
        }
        return $file;
    }

    public function absolutePath(File $file): string
    {
        $base = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
        return $base . DIRECTORY_SEPARATOR . $file->stored_path;
    }

    /** Guarda un archivo subido y registra sus metadatos. Devuelve el File. */
    public function store(array $uploaded, int $uploaderId): File
    {
        if (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw HttpException::unprocessable('Error al subir el archivo.');
        }
        if ($uploaded['size'] > self::MAX_SIZE) {
            throw HttpException::unprocessable('El archivo supera el tamaño máximo permitido (20 MB).');
        }

        $mime = (string) ($uploaded['type'] ?? '');
        if (! isset(self::ALLOWED_MIME[$mime])) {
            throw HttpException::unprocessable('Tipo de archivo no permitido. Se aceptan imágenes y PDF.');
        }

        $extension = self::ALLOWED_MIME[$mime];
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $base = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
        if (! is_dir($base)) {
            mkdir($base, 0775, true);
        }
        $destination = $base . DIRECTORY_SEPARATOR . $storedName;
        if (! move_uploaded_file($uploaded['tmp_name'], $destination)) {
            throw new HttpException(500, 'No se pudo almacenar el archivo.');
        }

        $id = $this->repository->create([
            ':original_name' => basename((string) ($uploaded['name'] ?? 'archivo')),
            ':stored_path' => $storedName,
            ':mime_type' => $mime,
            ':size_bytes' => (int) $uploaded['size'],
            ':uploader_id' => $uploaderId,
        ]);

        return $this->find($id);
    }
}
