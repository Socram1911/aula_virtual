<?php

declare(strict_types=1);

namespace App\Modules\H5p\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Session\Session;
use App\Core\Storage\S3Storage;
use App\Modules\H5p\Services\H5pService;

final class H5pController extends Controller
{
    private H5pService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new H5pService();
    }

    /** Sube un paquete .h5p y crea el bloque dentro de la unidad. */
    public function store(int $unitId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $file = $this->request->file('file');
        if ($file === null) {
            $this->backWithErrors(url('/units/' . $unitId . '/edit'), ['Debe seleccionar un archivo .h5p.']);
            return;
        }
        $title = (string) $this->request->input('title', '');
        $this->handle(function () use ($unitId, $file, $title): void {
            $this->service->storeBlock($unitId, $file, $title);
            Session::flash('_success', 'Contenido H5P agregado.');
        }, url('/units/' . $unitId . '/edit'));
    }

    /** Sirve los archivos del paquete H5P (h5p.json, content.json, librerías…) desde R2. */
    public function serve(int $id, string $path): void
    {
        Auth::requireAuth();
        $this->service->findBlock($id);

        $relative = H5pService::normalizePath($path);
        if ($relative === null) {
            throw HttpException::notFound('Archivo H5P no encontrado.');
        }

        (new S3Storage())->stream(
            'h5p/' . $id . '/' . $relative,
            H5pService::mimeFor($relative),
            null,
            true
        );
    }
}
