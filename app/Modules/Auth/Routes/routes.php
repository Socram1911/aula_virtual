<?php

declare(strict_types=1);

use App\Core\Router\Router;

$c = App\Modules\Auth\Controllers\AuthController::class;

Router::get('/login', "{$c}@showLogin");
Router::post('/login', "{$c}@login");
Router::post('/logout', "{$c}@logout");
Router::get('/register', "{$c}@showRegister");
Router::post('/register', "{$c}@register");
Router::get('/account/password', "{$c}@showChangePassword");
Router::post('/account/password', "{$c}@changePassword");
