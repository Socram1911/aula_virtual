<?php

declare(strict_types=1);

namespace App\Core\Csrf;

use App\Core\Exceptions\HttpException;
use App\Core\Session\Session;

/**
 * Protección CSRF por token de sesión.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    /** Devuelve (generando si hace falta) el token actual. */
    public static function token(): string
    {
        if (! Session::has(self::KEY)) {
            Session::put(self::KEY, bin2hex(random_bytes(32)));
        }
        return (string) Session::get(self::KEY);
    }

    /** Valida el token recibido contra el de sesión. */
    public static function validate(?string $token): void
    {
        if ($token === null || ! hash_equals(self::token(), $token)) {
            throw HttpException::forbidden('Token CSRF inválido. Recargue la página e intente nuevamente.');
        }
    }
}
