<?php

declare(strict_types=1);

namespace App\Modules\H5p\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Session\Session;
use App\Core\Storage\S3Storage;
use App\Modules\Grades\Services\GradeService;
use App\Modules\H5p\Services\H5pService;

final class H5pController extends Controller
{
    private H5pService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new H5pService();
    }

    /**
     * La devolución de resultados y el estado de contenido H5P llegan desde el
     * iframe del reproductor (jQuery.post, form-urlencoded) sin cabecera CSRF;
     * esas acciones validan la sesión y el rol por su cuenta.
     */
    protected function csrfExempt(): bool
    {
        return (bool) preg_match('#^/api/h5p/\d+/(result|state)$#', $this->request->path());
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
        $evaluable = (bool) $this->request->input('evaluable', false);
        $this->handle(function () use ($unitId, $file, $title, $evaluable): void {
            $this->service->storeBlock($unitId, $file, $title, $evaluable);
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

    /**
     * API: registra el envío de un bloque H5P evaluable.
     *
     * El runtime H5P llama a setFinishedUrl (vía jQuery.post, form-urlencoded)
     * con {contentId, score, maxScore, ...}. El resultado NO se revela aquí:
     * queda pendiente de liberación por el docente.
     */
    public function result(int $id): void
    {
        Auth::requireRole('STUDENT');

        // El runtime envía form-urlencoded (score/maxScore); el botón de envío de
        // ensayos usa JSON. Se admite cualquiera de los dos.
        $body = $this->request->json();
        $score = (float) ($this->request->input('score', $body['score'] ?? 0));
        $maxScore = (float) ($this->request->input('maxScore', $body['maxScore'] ?? 0));

        if ($score < 0) {
            $score = 0.0;
        }
        if ($maxScore > 0 && $score > $maxScore) {
            $score = $maxScore;
        }

        $result = $this->service->recordResult((int) Auth::id(), $id, $score, $maxScore);
        $this->json(['data' => $result]);
    }

    /**
     * API: estado de contenido (contentUserData) del runtime H5P.
     * GET devuelve el estado guardado; POST lo guarda (captura el texto de los
     * ensayos H5P.Essay).
     */
    public function state(int $id): void
    {
        Auth::requireRole('STUDENT');
        $studentId = (int) Auth::id();

        if ($this->request->method() === 'GET') {
            $state = $this->service->loadState($studentId, $id);
            header('Content-Type: application/json');
            echo json_encode($state ?? new \stdClass());
            exit;
        }

        $data = $this->request->input('data', '');
        $decoded = is_string($data) && $data !== '' ? json_decode($data, true) : null;
        if (! is_array($decoded)) {
            $decoded = [];
        }
        $this->service->saveState($studentId, $id, $decoded);
        $this->json(['data' => ['saved' => true]]);
    }

    /** Pantalla de corrección/liberación de un contenido evaluable (docente/admin). */
    public function submissions(int $assignmentId, int $blockId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $block = $this->service->findBlock($blockId);
        $this->render('H5p/submissions', [
            'title' => 'Evaluación H5P: ' . $block->title,
            'block' => $block,
            'assignmentId' => $assignmentId,
            'submissions' => $this->service->submissionsForBlock($blockId),
        ]);
    }

    /** Libera los resultados de un contenido evaluable (todos los estudiantes). */
    public function release(int $assignmentId, int $blockId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        try {
            $n = $this->service->releaseBlock($blockId);
        } catch (HttpException $e) {
            $this->backWithErrors(
                url('/saber-assignments/' . $assignmentId . '/h5p/' . $blockId . '/submissions'),
                $e->details() ?: [$e->getMessage()]
            );
            return;
        }
        Session::flash('_success', "Resultados liberados para {$n} estudiantes.");
        $this->redirect(url('/saber-assignments/' . $assignmentId . '/h5p/' . $blockId . '/submissions'));
    }

    /** Guarda la nota manual de una entrega (ensayo). */
    public function grade(int $submissionId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $score = (float) $this->request->input('score', 0);
        $maxScore = (float) $this->request->input('max_score', 20);
        $this->service->gradeSubmission($submissionId, $score, $maxScore, (int) Auth::id());
        Session::flash('_success', 'Nota guardada.');
        $this->back();
    }

    /** Datos de la entrega del estudiante actual para un bloque (usado en la vista). */
    public function mySubmission(int $blockId): void
    {
        Auth::requireRole('STUDENT');
        $sub = $this->service->submissionFor((int) Auth::id(), $blockId);
        $data = $sub === null ? null : [
            'status' => $sub['status'],
            'percentage' => $sub['percentage'] !== null ? (float) $sub['percentage'] : null,
            'note20' => $sub['percentage'] !== null ? (new GradeService())->to20((float) $sub['percentage']) : null,
        ];
        $this->json(['data' => $data]);
    }
}
