<?php

declare(strict_types=1);

use App\Core\Exceptions\Handler;
use App\Core\Router\Router;
use App\Core\Session\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/config.php';

// Sirve archivos estáticos existentes (CSS, JS, imágenes, fuentes) directamente.
// Necesario para el servidor embebido de PHP (`php -S`); inofensivo con Apache/nginx,
// donde .htaccess ya reenvía solo las rutas sin archivo.
$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$staticFile = __DIR__ . '/' . ltrim($requestedPath, '/');
if ($requestedPath !== '/' && is_file($staticFile)) {
    return false; // El servidor embebido sirve el archivo.
}

Session::start();

// Registro global de errores y excepciones.
Handler::register();

// Registra las rutas de todos los módulos.
Router::load(dirname(__DIR__) . '/app/Modules');

// Despacha la petición actual.
Router::dispatch();
