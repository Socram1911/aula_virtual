<?php

declare(strict_types=1);

use App\Core\Router\Router;

Router::get('/login', App\Modules\Auth\Controllers\AuthController::class . '@showLogin');
Router::post('/login', App\Modules\Auth\Controllers\AuthController::class . '@login');
Router::post('/logout', App\Modules\Auth\Controllers\AuthController::class . '@logout');
