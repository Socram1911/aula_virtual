<?php

declare(strict_types=1);

/**
 * Bootstrap de pruebas: autoload, configuración y conexión a la base de datos.
 * Las pruebas usan la misma base de datos de desarrollo (creada con `php database/migrate.php`).
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/config.php';
