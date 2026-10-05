<?php

declare(strict_types=1);

/**
 * Carga de configuración.
 *
 * Lee un archivo .env (si existe) y expone las claves como variables de entorno
 * con valores por defecto sensatos. No depende de paquetes externos.
 */

/**
 * Carga un archivo .env sencillo (CLAVE=VALOR, admite comillas).
 */
function env_load(string $path): void
{
    if (! is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '') {
            continue;
        }
        // Quita comillas simples o dobles envolventes.
        $len = strlen($value);
        if ($len >= 2 && (($value[0] === '"' && $value[$len - 1] === '"') || ($value[0] === "'" && $value[$len - 1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

env_load(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

/**
 * Devuelve un valor de configuración con un valor por defecto.
 */
function config(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    if ($value === 'true') {
        return true;
    }
    if ($value === 'false') {
        return false;
    }
    return $value;
}
