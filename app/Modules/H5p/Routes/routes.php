<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\H5p\Controllers\H5pController;

$c = H5pController::class;
Router::post('/units/{id}/h5p', "{$c}@store");
Router::get('/h5p/{id}/{path}', "{$c}@serve");
// Resultado (setFinished): el contentId (blockId) viaja en el cuerpo.
Router::post('/api/h5p/result', "{$c}@result");
// Estado (contentUserData): contentId y subContentId van en la URL.
Router::get('/api/h5p/state/{id}/{sub}', "{$c}@state");
Router::post('/api/h5p/state/{id}/{sub}', "{$c}@state");
Router::get('/api/h5p/{id}/submission', "{$c}@mySubmission");
Router::get('/saber-assignments/{assignmentId}/h5p/{blockId}/submissions', "{$c}@submissions");
Router::post('/saber-assignments/{assignmentId}/h5p/{blockId}/release', "{$c}@release");
Router::post('/api/h5p/submission/{id}/grade', "{$c}@grade");
