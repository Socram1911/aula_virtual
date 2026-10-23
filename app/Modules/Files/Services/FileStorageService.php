<?php

declare(strict_types=1);

namespace App\Modules\Files\Services;

use App\Core\Exceptions\HttpException;
use App\Core\Storage\S3Storage;
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

    /** Clave del objeto en R2 para un archivo dado. */
    private function objectKey(File $file): string
    {
        return 'uploads/' . $file->stored_path;
    }

    /** Transmite el archivo desde R2 con control de acceso y termina el script. */
    public function stream(File $file): void
    {
        $inline = str_starts_with($file->mime_type, 'image/') || $file->mime_type === 'application/pdf';
        (new S3Storage())->stream(
            $this->objectKey($file),
            $file->mime_type,
            $file->original_name,
            $inline
        );
    }

    /** Guarda un archivo subido y registra sus metadatos. Devuelve el File. */
    public function store(array $uploaded, int $uploaderId): File
    {
        $error = (int) ($uploaded['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw HttpException::unprocessable(self::uploadErrorMessage($error));
        }
        if ($uploaded['size'] > self::MAX_SIZE) {
            throw HttpException::unprocessable('El archivo supera el tamaño máximo permitido (20 MB).');
        }

        $mime = self::detectMime($uploaded);
        if (! isset(self::ALLOWED_MIME[$mime])) {
            throw HttpException::unprocessable('Tipo de archivo no permitido. Se aceptan imágenes y PDF.');
        }

        $extension = self::ALLOWED_MIME[$mime];
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

        try {
            (new S3Storage())->put('uploads/' . $storedName, $uploaded['tmp_name'], $mime);
        } catch (\Throwable $e) {
            error_log('No se pudo subir el archivo a R2: ' . $e->getMessage());
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

    /**
     * Determina el MIME del archivo subido. El tipo que envía el navegador no
     * es confiable (puede venir vacío o como application/octet-stream), así que
     * se valida contra el contenido real (finfo) y, como último recurso, contra
     * la extensión del nombre original.
     */
    private static function detectMime(array $uploaded): string
    {
        $candidates = [];

        $client = (string) ($uploaded['type'] ?? '');
        if ($client !== '') {
            $candidates[] = $client;
        }

        $tmp = (string) ($uploaded['tmp_name'] ?? '');
        if ($tmp !== '' && is_file($tmp) && function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = @finfo_file($finfo, $tmp);
                @finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $candidates[] = $detected;
                }
            }
        }

        $ext = strtolower(pathinfo((string) ($uploaded['name'] ?? ''), PATHINFO_EXTENSION));
        $byExtension = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => null,
        };
        if ($byExtension !== null) {
            $candidates[] = $byExtension;
        }

        foreach ($candidates as $mime) {
            if (isset(self::ALLOWED_MIME[$mime])) {
                return $mime;
            }
        }

        // Ningún candidato es válido: se devuelve uno no permitido para que el
        // llamador muestre el error de tipo no permitido.
        return $candidates[0] ?? 'application/octet-stream';
    }

    private static function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el límite de tamaño del servidor.',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió de forma incompleta. Intente nuevamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal para procesar el archivo.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el servidor.',
            default => 'Error al subir el archivo.',
        };
    }
}
