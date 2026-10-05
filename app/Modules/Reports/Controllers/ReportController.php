<?php

declare(strict_types=1);

namespace App\Modules\Reports\Controllers;

use App\Core\Controller;
use App\Modules\Reports\Services\ReportService;

final class ReportController extends Controller
{
    private ReportService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ReportService();
    }

    public function index(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Reports/index', [
            'title' => 'Reportes',
            'studentsPerSection' => $this->service->studentsPerSection(),
            'studentsPerTrack' => $this->service->studentsPerTrack(),
            'sabersPerTrack' => $this->service->sabersPerTrack(),
            'teachersPerSaber' => $this->service->teachersPerSaber(),
            'academicResults' => $this->service->academicResults(),
            'evaluationResults' => $this->service->evaluationResults(),
        ]);
    }
}
