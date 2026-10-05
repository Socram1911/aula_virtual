<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Reports\Controllers\ReportController;

Router::get('/reports', ReportController::class . '@index');
