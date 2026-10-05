<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;

/**
 * Conexión PDO única (singleton) configurada desde .env.
 * Toda consulta debe usar sentencias preparadas (nunca concatenar entrada del usuario).
 */
final class Connection
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $driver = (string) config('DB_DRIVER', 'mysql');
            $host = (string) config('DB_HOST', '127.0.0.1');
            $port = (string) config('DB_PORT', '3306');
            $name = (string) config('DB_NAME', 'aula_virtual');
            $user = (string) config('DB_USER', 'root');
            $pass = (string) config('DB_PASS', '');

            $dsn = "{$driver}:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Emulación para permitir reutilizar marcadores con nombre (:x) en una misma sentencia.
                    PDO::ATTR_EMULATE_PREPARES => true,
                ]);
            } catch (PDOException $e) {
                throw new PDOException(
                    'No se pudo conectar a la base de datos. Verifique la configuración en .env: ' . $e->getMessage(),
                    (int) $e->getCode()
                );
            }
        }

        return self::$pdo;
    }

    /** Ejecuta una sentencia preparada y devuelve el PDOStatement. */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Ejecuta y devuelve una sola fila (o null). */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Ejecuta y devuelve todas las filas. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Ejecuta y devuelve el id insertado. */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }
}
