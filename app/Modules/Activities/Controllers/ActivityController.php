<?php

declare(strict_types=1);

namespace App\Modules\Activities\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Session\Session;
use App\Modules\Activities\Services\ActivityService;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Units\Services\UnitEditorService;

final class ActivityController extends Controller
{
    private ActivityService $service;
    private UnitEditorService $editor;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ActivityService();
        $this->editor = new UnitEditorService();
    }

    /** Crea un bloque de actividad dentro de una unidad. */
    public function store(int $unitId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $input = $this->request->all();
        $settings = $this->service->validateSettings([
            'type' => $input['activity_type'] ?? '',
            'question' => $input['question'] ?? '',
            'options' => $input['options'] ?? [],
            'classification' => $input['classification'] ?? 'FORMATIVE',
        ]);

        $block = $this->editor->addBlock($unitId, [
            'type' => 'ACTIVITY',
            'title' => trim((string) ($input['title'] ?? 'Actividad')),
            'content' => '',
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
        ]);
        AuditService::log('crear Actividad', 'ContentBlock', (int) $block->id, null, $settings);
        Session::flash('_success', 'Actividad creada.');
        $this->redirect(url('/units/' . $unitId . '/edit'));
    }

    /** API: envía la respuesta de una actividad (corrección en servidor). */
    public function submit(int $blockId): void
    {
        Auth::requireRole('STUDENT');
        $answer = $this->request->json()['answer'] ?? null;
        $result = $this->service->submit((int) Auth::id(), $blockId, $answer);
        $this->json(['data' => $result]);
    }
}
