<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;

/**
 * Conexión PDO única (singleton) configurada desde .env.
 *
 * Soporta MySQL/MariaDB (desarrollo local) y PostgreSQL (producción, p. ej. Neon).
 * Toda consulta debe usar sentencias preparadas (nunca concatenar entrada del usuario).
 */
final class Connection
{
    private static ?PDO $pdo = null;

    public static function driver(): string
    {
        return (string) config('DB_DRIVER', 'mysql');
    }

    public static function isPostgres(): bool
    {
        return self::driver() === 'pgsql';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $driver = self::driver();
            $host = (string) config('DB_HOST', '127.0.0.1');
            $port = (string) config('DB_PORT', $driver === 'pgsql' ? '5432' : '3306');
            $name = (string) config('DB_NAME', 'aula_virtual');
            $user = (string) config('DB_USER', 'root');
            $pass = (string) config('DB_PASS', '');

            if ($driver === 'pgsql') {
                $sslmode = (string) config('DB_SSLMODE', 'require');
                $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$sslmode}";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            }

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Emulación: permite reutilizar marcadores con nombre en MySQL.
                    PDO::ATTR_EMULATE_PREPARES => $driver !== 'pgsql',
                ]);

                if ($driver === 'mysql') {
                    // Alinea la zona horaria de la sesión MySQL con la aplicación (UTC).
                    self::$pdo->exec("SET time_zone = '+00:00'");
                }
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

    /** Ejecuta un INSERT y devuelve el id generado. */
    public static function insert(string $sql, array $params = []): int
    {
        if (self::isPostgres()) {
            // PostgreSQL: INSERT ... RETURNING id (los PK usan columna "id").
            $stmt = self::pdo()->prepare($sql . ' RETURNING id');
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        }
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Cláusula de "upsert" portable: devuelve el texto a concatenar después de VALUES (...).
     *   MySQL:  " ON DUPLICATE KEY UPDATE "
     *   PgSQL:  " ON CONFLICT (cols) DO UPDATE SET "
     *
     * La parte SET que sigue debe usar `CASE WHEN` (portable) y referirse a las columnas
     * existentes por su nombre simple; los valores nuevos se referencian con sus parámetros.
     */
    public static function onConflict(string $conflictColumns): string
    {
        return self::isPostgres()
            ? ' ON CONFLICT (' . $conflictColumns . ') DO UPDATE SET '
            : ' ON DUPLICATE KEY UPDATE ';
    }

    /** INSERT que no falla si el registro ya existe (para sembrado de roles, etc.). */
    public static function insertIgnore(string $table, string $column, string $value): void
    {
        if (self::isPostgres()) {
            self::run(
                "INSERT INTO {$table} ({$column}) VALUES (:v) ON CONFLICT ({$column}) DO NOTHING",
                [':v' => $value]
            );
        } else {
            self::run("INSERT IGNORE INTO {$table} ({$column}) VALUES (:v)", [':v' => $value]);
        }
    }
}
