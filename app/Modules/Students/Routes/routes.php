<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Students\Controllers\StudentsController;

$c = StudentsController::class;
Router::get('/students', "{$c}@index");
Router::post('/sections/{id}/enroll', "{$c}@enroll");
Router::post('/sections/{id}/withdraw', "{$c}@withdraw");
