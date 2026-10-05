<?php

declare(strict_types=1);

namespace App\Core\Auth;

/**
 * Hash y verificación de contraseñas (Argon2id con respaldo bcrypt).
 */
final class PasswordHasher
{
    public static function hash(string $plain): string
    {
        return password_hash($plain, self::algorithm());
    }

    public static function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm());
    }

    private static function algorithm(): string|int
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }
}
