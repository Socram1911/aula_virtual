<?php

declare(strict_types=1);

namespace App\Modules\H5p\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Session\Session;
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

    /** Sirve los archivos del paquete H5P (h5p.json, content.json, librerías…). */
    public function serve(int $id, string $path): void
    {
        Auth::requireAuth();
        $target = $this->service->resolve($id, $path);
        if ($target === null || ! is_file($target)) {
            throw new \App\Core\Exceptions\HttpException(404, 'Archivo H5P no encontrado.');
        }

        $mime = $this->mime($target);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($target));
        if (in_array($mime, ['image/svg+xml', 'image/gif', 'image/png', 'image/jpeg'], true)) {
            header('Content-Disposition: inline');
        }
        readfile($target);
        exit;
    }

    private function mime(string $path): string
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
            default => 'application/octet-stream',
        };
    }
}
