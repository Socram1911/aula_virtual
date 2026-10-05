<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\H5p\Controllers\H5pController;

$c = H5pController::class;
Router::post('/units/{id}/h5p', "{$c}@store");
Router::get('/h5p/{id}/{path}', "{$c}@serve");
