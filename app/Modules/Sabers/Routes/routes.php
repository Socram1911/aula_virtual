<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Sabers\Controllers\AssignmentController;
use App\Modules\Sabers\Controllers\SaberController;

$s = SaberController::class;
$c = AssignmentController::class;

// Saberes
Router::get('/sabers', "{$s}@index");
Router::get('/sabers/create', "{$s}@create");
Router::post('/sabers', "{$s}@store");
Router::get('/sabers/{id}/edit', "{$s}@edit");
Router::post('/sabers/{id}', "{$s}@update");
Router::post('/sabers/{id}/delete', "{$s}@destroy");

// Unidades
Router::get('/sabers/{id}/units', "{$s}@units");
Router::get('/sabers/{id}/units/create', "{$s}@createUnit");
Router::post('/sabers/{id}/units', "{$s}@storeUnit");
Router::get('/units/{id}/edit-meta', "{$s}@editUnit");
Router::post('/units/{id}', "{$s}@updateUnit");
Router::post('/units/{id}/publish', "{$s}@publishUnit");
Router::post('/units/{id}/delete', "{$s}@deleteUnit");

// Asignaciones
Router::get('/saber-assignments', "{$c}@index");
Router::get('/saber-assignments/create', "{$c}@create");
Router::post('/saber-assignments', "{$c}@store");
Router::get('/saber-assignments/{id}', "{$c}@show");
Router::post('/saber-assignments/{id}/components', "{$c}@storeComponents");
Router::get('/saber-assignments/{id}/edit', "{$c}@edit");
Router::post('/saber-assignments/{id}', "{$c}@update");
Router::post('/saber-assignments/{id}/status', "{$c}@setStatus");
Router::post('/saber-assignments/{id}/delete', "{$c}@destroy");
