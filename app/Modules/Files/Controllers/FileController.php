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
        $this->storage->stream($file);
    }
}
