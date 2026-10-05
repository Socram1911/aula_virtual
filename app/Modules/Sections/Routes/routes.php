<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Sections\Controllers\SectionController;

$c = SectionController::class;
Router::get('/sections', "{$c}@index");
Router::get('/sections/create', "{$c}@create");
Router::post('/sections', "{$c}@store");
Router::get('/sections/{id}', "{$c}@show");
Router::get('/sections/{id}/edit', "{$c}@edit");
Router::post('/sections/{id}', "{$c}@update");
Router::post('/sections/{id}/delete', "{$c}@destroy");
