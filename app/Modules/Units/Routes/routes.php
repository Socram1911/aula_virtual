<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Units\Controllers\UnitEditorController;

$c = UnitEditorController::class;
Router::get('/units/{id}/edit', "{$c}@edit");
Router::post('/units/{id}/blocks', "{$c}@store");
Router::post('/blocks/{id}', "{$c}@update");
Router::post('/blocks/{id}/duplicate', "{$c}@duplicate");
Router::post('/blocks/{id}/move', "{$c}@move");
Router::post('/blocks/{id}/toggle', "{$c}@toggle");
Router::post('/blocks/{id}/delete', "{$c}@destroy");
