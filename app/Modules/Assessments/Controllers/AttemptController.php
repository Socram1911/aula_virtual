<?php

declare(strict_types=1);

namespace App\Modules\Assessments\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Modules\Assessments\Repositories\AttemptRepository;
use App\Modules\Assessments\Services\AssessmentService;
use App\Modules\Assessments\Services\AttemptService;

final class AttemptController extends Controller
{
    private AttemptService $service;
    private AttemptRepository $attempts;
    private AssessmentService $assessments;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AttemptService();
        $this->attempts = new AttemptRepository();
        $this->assessments = new AssessmentService();
    }

    /** Página del estudiante para presentar la evaluación. */
    public function take(int $assessmentId): void
    {
        $this->requireRole('STUDENT');
        $studentId = (int) Auth::id();
        $assessment = $this->assessments->find($assessmentId);

        $attempt = $this->attempts->lastAttempt($assessmentId, $studentId);
        if ($attempt === null || $attempt['status'] !== 'IN_PROGRESS') {
            $payload = $this->service->start($studentId, $assessmentId);
        } else {
            if ($assessment->time_limit !== null && (time() - strtotime((string) $attempt['started_at']) > $assessment->time_limit)) {
                $this->attempts->expire((int) $attempt['id']);
                $payload = $this->service->start($studentId, $assessmentId);
            } else {
                $payload = [
                    'attempt_id' => (int) $attempt['id'],
                    'attempt_number' => (int) $attempt['attempt_number'],
                    'time_limit_seconds' => $assessment->time_limit,
                    'questions' => $this->publicQuestions($assessmentId),
                ];
            }
        }

        $this->render('Assessments/take', [
            'title' => $assessment->title,
            'assessment' => $assessment,
            'payload' => $payload,
        ]);
    }

    /** Envío del formulario (server-rendered). */
    public function submitForm(int $assessmentId): void
    {
        $this->requireRole('STUDENT');
        $attemptId = (int) $this->request->input('attempt_id', 0);
        $answers = $this->request->input('answers', []);
        $result = $this->service->submit((int) Auth::id(), $attemptId, is_array($answers) ? $answers : []);
        $this->render('Assessments/result', [
            'title' => 'Resultado',
            'assessment' => $this->assessments->find($assessmentId),
            'result' => $result,
        ]);
    }

    // ===== API JSON =====

    public function apiStart(int $assessmentId): void
    {
        Auth::requireRole('STUDENT');
        $this->json(['data' => $this->service->start((int) Auth::id(), $assessmentId)], 201);
    }

    public function apiSubmit(int $assessmentId, int $attemptId): void
    {
        Auth::requireRole('STUDENT');
        $answers = $this->request->json()['answers'] ?? [];
        $result = $this->service->submit((int) Auth::id(), $attemptId, is_array($answers) ? $answers : []);
        $this->json(['data' => $result]);
    }

    private function publicQuestions(int $assessmentId): array
    {
        return array_map(function ($q) {
            $q['options'] = array_map(fn ($o) => ['id' => $o['id'], 'option_text' => $o['option_text']], $q['options'] ?? []);
            return $q;
        }, $this->assessments->questions($assessmentId));
    }
}
