<?php

declare(strict_types=1);

use App\Core\Exceptions\Handler;
use App\Core\Router\Router;
use App\Core\Session\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/config.php';

Session::start();

// Registro global de errores y excepciones.
Handler::register();

// Registra las rutas de todos los módulos.
Router::load(dirname(__DIR__) . '/app/Modules');

// Despacha la petición actual.
Router::dispatch();
