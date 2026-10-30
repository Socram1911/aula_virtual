<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\H5p\Controllers\H5pController;

$c = H5pController::class;
Router::post('/units/{id}/h5p', "{$c}@store");
Router::get('/h5p/{id}/{path}', "{$c}@serve");
Router::post('/api/h5p/{id}/result', "{$c}@result");
Router::get('/api/h5p/{id}/state', "{$c}@state");
Router::post('/api/h5p/{id}/state', "{$c}@state");
Router::get('/api/h5p/{id}/submission', "{$c}@mySubmission");
Router::get('/saber-assignments/{assignmentId}/h5p/{blockId}/submissions', "{$c}@submissions");
Router::post('/saber-assignments/{assignmentId}/h5p/{blockId}/release', "{$c}@release");
Router::post('/api/h5p/submission/{id}/grade', "{$c}@grade");
