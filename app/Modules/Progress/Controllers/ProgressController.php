<?php

declare(strict_types=1);

namespace App\Modules\Progress\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Progress\Services\ProgressService;

final class ProgressController extends Controller
{
    private ProgressService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ProgressService();
    }

    /** GET /api/student/progress */
    public function studentProgress(): void
    {
        Auth::requireRole('STUDENT');
        $this->json(['data' => $this->service->summary((int) Auth::id())]);
    }

    /** POST /api/progress/block */
    public function markBlock(): void
    {
        Auth::requireRole('STUDENT');
        $blockId = (int) ($this->request->json()['block_id'] ?? 0);
        $studentId = (int) Auth::id();

        $block = Connection::fetch('SELECT id, unit_id FROM content_blocks WHERE id = :id', [':id' => $blockId]);
        if ($block === null) {
            throw HttpException::notFound('Bloque no encontrado.');
        }
        $this->service->markBlockCompleted($studentId, $blockId);

        $unit = Connection::fetch('SELECT percentage, status FROM student_unit_progress WHERE student_id = :s AND unit_id = :u', [':s' => $studentId, ':u' => (int) $block['unit_id']]);
        $this->json(['data' => [
            'block_id' => $blockId,
            'block_status' => 'COMPLETED',
            'unit_percentage' => (float) ($unit['percentage'] ?? 0),
            'unit_completed' => ($unit['status'] ?? '') === 'COMPLETED',
        ]]);
    }

    /** POST /api/progress/view — registra que el estudiante vio un bloque. */
    public function viewBlock(): void
    {
        Auth::requireRole('STUDENT');
        $blockId = (int) ($this->request->json()['block_id'] ?? 0);
        $block = Connection::fetch('SELECT id FROM content_blocks WHERE id = :id', [':id' => $blockId]);
        if ($block === null) {
            throw HttpException::notFound('Bloque no encontrado.');
        }
        $this->service->recordView((int) Auth::id(), $blockId);
        $this->json(['data' => ['recorded' => true]]);
    }

    /** POST /api/progress/video — eventos del reproductor de video (inicio, avance, finalizado). */
    public function videoEvent(): void
    {
        Auth::requireRole('STUDENT');
        $studentId = (int) Auth::id();
        $blockId = (int) ($this->request->json()['block_id'] ?? 0);
        $event = (string) ($this->request->json()['event'] ?? '');
        $percent = (float) ($this->request->json()['percent'] ?? 0);

        if ($event === 'video_started') {
            $this->service->recordView($studentId, $blockId);
        } elseif ($event === 'video_progress') {
            $this->service->recordVideoProgress($studentId, $blockId, $percent);
        } elseif ($event === 'video_completed') {
            $this->service->markBlockCompleted($studentId, $blockId);
        }

        $this->json(['data' => ['recorded' => true]]);
    }
}
