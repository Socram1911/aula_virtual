<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Users\Controllers\UserController;

$c = UserController::class;
Router::get('/users', "{$c}@index");
Router::get('/teachers', "{$c}@teachers");
Router::get('/users/create', "{$c}@create");
Router::post('/users', "{$c}@store");
Router::get('/users/{id}/edit', "{$c}@edit");
Router::post('/users/{id}', "{$c}@update");
Router::post('/users/{id}/delete', "{$c}@destroy");
