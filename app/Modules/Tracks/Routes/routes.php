<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Tracks\Controllers\TrackController;

$c = TrackController::class;
Router::get('/tracks', "{$c}@index");
Router::get('/tracks/create', "{$c}@create");
Router::post('/tracks', "{$c}@store");
Router::get('/tracks/{id}/edit', "{$c}@edit");
Router::post('/tracks/{id}', "{$c}@update");
Router::post('/tracks/{id}/delete', "{$c}@destroy");
