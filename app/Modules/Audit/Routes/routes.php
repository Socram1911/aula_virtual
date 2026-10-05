<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Audit\Controllers\AuditController;

Router::get('/audit', AuditController::class . '@index');
