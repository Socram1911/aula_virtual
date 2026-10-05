<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Learning\Controllers\LearningController;

$c = LearningController::class;
Router::get('/my-section', "{$c}@mySection");
Router::get('/my-activities', "{$c}@myActivities");
Router::get('/my-sabers', "{$c}@mySabers");
Router::get('/sabers/{id}', "{$c}@saber");
Router::get('/units/{id}', "{$c}@unit");
