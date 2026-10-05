<?php

declare(strict_types=1);

namespace App\Modules\Files\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Modules\Files\Services\FileStorageService;

final class FileController extends Controller
{
    private FileStorageService $storage;

    public function __construct()
    {
        parent::__construct();
        $this->storage = new FileStorageService();
    }

    /** Descarga/sirve un archivo con control de acceso (solo usuarios autenticados). */
    public function download(int $id): void
    {
        Auth::requireAuth();
        $file = $this->storage->find($id);
        $path = $this->storage->absolutePath($file);
        if (! is_file($path)) {
            throw new \App\Core\Exceptions\HttpException(404, 'El archivo no está disponible.');
        }

        $inline = str_starts_with($file->mime_type, 'image/') || $file->mime_type === 'application/pdf';
        header('Content-Type: ' . $file->mime_type);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $file->original_name . '"');
        readfile($path);
        exit;
    }
}
