<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Assessments\Controllers\AssessmentController;
use App\Modules\Assessments\Controllers\AttemptController;

$c = AssessmentController::class;
$t = AttemptController::class;

// Gestión docente
Router::get('/assessments', "{$c}@index");
Router::get('/assessments/create', "{$c}@create");
Router::post('/assessments', "{$c}@store");
Router::get('/assessments/{id}/edit', "{$c}@edit");
Router::post('/assessments/{id}', "{$c}@update");
Router::post('/assessments/{id}/status', "{$c}@setStatus");
Router::post('/assessments/{id}/delete', "{$c}@destroy");
Router::get('/assessments/{id}/questions', "{$c}@questions");
Router::post('/assessments/{id}/questions', "{$c}@storeQuestion");
Router::post('/questions/{id}/delete', "{$c}@deleteQuestion");

// Estudiante
Router::get('/assessments/{id}/take', "{$t}@take");
Router::post('/assessments/{id}/attempt', "{$t}@submitForm");

// API JSON
Router::post('/api/assessment/{id}/attempt', "{$t}@apiStart");
Router::post('/api/assessment/{id}/attempt/{attemptId}/submit', "{$t}@apiSubmit");
