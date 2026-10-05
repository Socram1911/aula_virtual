<?php

declare(strict_types=1);

use App\Core\Router\Router;
use App\Modules\Files\Controllers\FileController;

Router::get('/files/{id}', FileController::class . '@download');
