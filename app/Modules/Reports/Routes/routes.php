<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Reports\Controllers\ReportController;

$c = ReportController::class;
Router::get('/reports', "{$c}@index");
Router::get('/reports/unit/{id}', "{$c}@unitContent");
