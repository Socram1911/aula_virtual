<?php

declare(strict_types=1);

use App\Core\Csrf\Csrf;

if (! function_exists('e')) {
    /** Escapa texto para salida HTML segura. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('url')) {
    /** Construye una URL absoluta a partir de una ruta. */
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('APP_URL', ''), '/');
        if ($path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        }
        return $base . $path;
    }
}

if (! function_exists('asset')) {
    /** URL de un recurso estático. */
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (! function_exists('csrf_field')) {
    /** Genera el input oculto del token CSRF. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
    }
}

if (! function_exists('old')) {
    /** Valor anterior del formulario (para re-poblar tras errores). */
    function old(string $key, mixed $default = ''): mixed
    {
        $flash = $_SESSION['_old'][$key] ?? $default;
        return $flash;
    }
}

if (! function_exists('selected')) {
    /** Devuelve 'selected' cuando los valores coinciden. */
    function selected(mixed $a, mixed $b): string
    {
        return (string) $a === (string) $b ? 'selected' : '';
    }
}

if (! function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
