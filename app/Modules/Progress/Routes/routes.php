<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Progress\Controllers\ProgressController;

$c = ProgressController::class;
Router::get('/api/student/progress', "{$c}@studentProgress");
Router::post('/api/progress/block', "{$c}@markBlock");
Router::post('/api/progress/view', "{$c}@viewBlock");
Router::post('/api/progress/video', "{$c}@videoEvent");
