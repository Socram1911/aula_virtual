<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Dashboard\Controllers\DashboardController;

Router::get('/', DashboardController::class . '@index');
Router::get('/dashboard', DashboardController::class . '@index');
