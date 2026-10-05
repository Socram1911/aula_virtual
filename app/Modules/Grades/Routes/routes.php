<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Grades\Controllers\GradeController;

$c = GradeController::class;
Router::get('/grades', "{$c}@index");
Router::get('/api/student/grades', "{$c}@apiStudentGrades");
Router::get('/api/teacher/assignment/{id}/students', "{$c}@apiAssignmentStudents");
