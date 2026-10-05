<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\AcademicPeriods\Controllers\AcademicPeriodController;

$c = AcademicPeriodController::class;
Router::get('/academic-periods', "{$c}@index");
Router::get('/academic-periods/create', "{$c}@create");
Router::post('/academic-periods', "{$c}@store");
Router::get('/academic-periods/{id}/edit', "{$c}@edit");
Router::post('/academic-periods/{id}', "{$c}@update");
Router::post('/academic-periods/{id}/transition', "{$c}@transition");
Router::post('/academic-periods/{id}/delete', "{$c}@destroy");
