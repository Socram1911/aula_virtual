<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Activities\Controllers\ActivityController;

$c = ActivityController::class;
Router::post('/units/{id}/activities', "{$c}@store");
Router::post('/api/activity/{id}/submit', "{$c}@submit");
