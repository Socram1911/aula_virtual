<?php

declare(strict_types=1);

namespace App\Modules\Learning\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Progress\Services\UnlockService;
use App\Modules\Sabers\Repositories\SaberRepository;
use App\Modules\Sabers\Services\AssignmentService;
use App\Modules\Students\Services\EnrollmentService;
use App\Modules\Content\Repositories\ContentBlockRepository;

final class LearningController extends Controller
{
    private AssignmentService $assignments;
    private EnrollmentService $enrollments;
    private SaberRepository $sabers;
    private ContentBlockRepository $blocks;
    private UnlockService $unlock;

    public function __construct()
    {
        parent::__construct();
        $this->assignments = new AssignmentService();
        $this->enrollments = new EnrollmentService();
        $this->sabers = new SaberRepository();
        $this->blocks = new ContentBlockRepository();
        $this->unlock = new UnlockService();
    }

    public function myActivities(): void
    {
        $this->requireRole('STUDENT');
        $studentId = (int) Auth::id();
        // Evaluaciones publicadas accesibles al estudiante, tanto las ligadas a una
        // asignación de Saber como las ligadas a una unidad de su Saber.
        $assessments = Connection::fetchAll(
            'SELECT a.id, a.title, a.max_score, a.passing_score, a.status
             FROM assessments a
             JOIN saber_assignments sa ON sa.id = a.saber_assignment_id AND sa.status = :pub
             JOIN section_students ss ON ss.section_id = sa.section_id AND ss.student_id = :s AND ss.status = :enr
             WHERE a.status = :pub
             UNION
             SELECT a.id, a.title, a.max_score, a.passing_score, a.status
             FROM assessments a
             JOIN units u ON u.id = a.unit_id AND u.status = :upub
             JOIN saber_assignments sa2 ON sa2.saber_id = u.saber_id AND sa2.status = :pub
             JOIN section_students ss2 ON ss2.section_id = sa2.section_id AND ss2.student_id = :s AND ss2.status = :enr
             WHERE a.status = :pub
             ORDER BY id DESC',
            [':s' => $studentId, ':enr' => 'ENROLLED', ':pub' => 'PUBLISHED', ':upub' => 'PUBLISHED']
        );
        $this->render('Learning/my_activities', [
            'title' => 'Actividades y Evaluaciones',
            'assessments' => $assessments,
        ]);
    }

    public function mySection(): void
    {
        $this->requireRole('STUDENT');
        $studentId = (int) Auth::id();
        $this->render('Learning/my_section', [
            'title' => 'Mi Sección',
            'sections' => $this->enrollments->sectionsForStudent($studentId),
        ]);
    }

    public function mySabers(): void
    {
        $this->requireRole('STUDENT');
        $studentId = (int) Auth::id();
        $assignments = $this->assignments->forStudent($studentId);
        foreach ($assignments as $a) {
            $progress = Connection::fetch(
                'SELECT percentage FROM student_saber_progress WHERE student_id = :s AND saber_assignment_id = :a',
                [':s' => $studentId, ':a' => (int) $a->id]
            );
            $a->progress = (float) ($progress['percentage'] ?? 0);
        }
        $this->render('Learning/my_sabers', [
            'title' => 'Mis Saberes',
            'items' => $assignments,
            'firstName' => (string) (Auth::user()['first_name'] ?? ''),
        ]);
    }

    /** Detalle de un Saber (estudiante): unidades visibles con desbloqueo. */
    public function saber(int $id): void
    {
        $this->requireAuth();
        $saber = $this->sabers->find($id);
        if ($saber === null) {
            throw HttpException::notFound('Saber no encontrado.');
        }

        $units = $this->sabers->units($id);
        $isStudent = Auth::hasRole('STUDENT');

        if ($isStudent) {
            $studentId = (int) Auth::id();
            $units = $this->unlock->unlockedUnits($studentId, $id, array_map(fn ($u) => $u->toArray() + ['id' => $u->id], $units));
        } else {
            $units = array_map(fn ($u) => $u->toArray() + ['id' => $u->id, 'unlocked' => true], $units);
        }

        $this->render('Learning/saber', [
            'title' => $saber->name,
            'saber' => $saber,
            'units' => $units,
            'isStudent' => $isStudent,
        ]);
    }

    /** Contenido de una unidad (estudiante): bloques activos. */
    public function unit(int $id): void
    {
        $this->requireAuth();
        $unit = $this->sabers->findUnit($id);
        if ($unit === null) {
            throw HttpException::notFound('Unidad no encontrada.');
        }

        if (Auth::hasRole('STUDENT')) {
            $units = array_map(fn ($u) => $u->toArray() + ['id' => $u->id], $this->sabers->units($unit->saber_id));
            if (! $this->unlock->isUnlocked((int) Auth::id(), $unit->saber_id, $id, $units)) {
                throw HttpException::forbidden('Debe completar la unidad anterior para acceder a esta unidad.');
            }
            if ($unit->status !== 'PUBLISHED') {
                throw HttpException::notFound('Esta unidad no está disponible.');
            }
        }

        $blocks = array_values(array_filter(
            $this->blocks->blocks($id),
            fn ($b) => Auth::hasRole('STUDENT') ? $b->status === 'ACTIVE' : true
        ));

        $this->render('Learning/unit', [
            'title' => $unit->title,
            'unit' => $unit,
            'blocks' => $blocks,
        ]);
    }
}
