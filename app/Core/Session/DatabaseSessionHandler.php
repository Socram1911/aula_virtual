<?php

declare(strict_types=1);

namespace App\Core\Session;

use App\Core\Database\Connection;

/**
 * Guarda las sesiones en la base de datos (tabla `sessions`).
 *
 * Necesario en entornos con disco efímero (Render, Cloud Run), donde las
 * sesiones por archivos se pierden en cada reinicio o "cold start".
 */
final class DatabaseSessionHandler implements \SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $row = Connection::fetch('SELECT payload FROM sessions WHERE id = :id', [':id' => $id]);

        return $row === null ? '' : (string) $row['payload'];
    }

    public function write(string $id, string $data): bool
    {
        $last = time();

        if (Connection::isPostgres()) {
            Connection::run(
                'INSERT INTO sessions (id, payload, last_activity) VALUES (:id, :payload, :last)
                 ON CONFLICT (id) DO UPDATE SET payload = EXCLUDED.payload, last_activity = EXCLUDED.last_activity',
                [':id' => $id, ':payload' => $data, ':last' => $last]
            );
        } else {
            Connection::run(
                'INSERT INTO sessions (id, payload, last_activity) VALUES (:id, :payload, :last)
                 ON DUPLICATE KEY UPDATE payload = VALUES(payload), last_activity = VALUES(last_activity)',
                [':id' => $id, ':payload' => $data, ':last' => $last]
            );
        }

        return true;
    }

    public function destroy(string $id): bool
    {
        Connection::run('DELETE FROM sessions WHERE id = :id', [':id' => $id]);

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = Connection::run(
            'DELETE FROM sessions WHERE last_activity < :threshold',
            [':threshold' => time() - $max_lifetime]
        );

        return $stmt->rowCount();
    }
}
