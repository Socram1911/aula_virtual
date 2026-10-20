<?php

declare(strict_types=1);

/**
 * Ejecutor de migraciones.
 *
 *   - MySQL/MariaDB (desarrollo): crea la base de datos y aplica schema.sql.
 *   - PostgreSQL (producción, p. ej. Neon): la base de datos ya existe en el
 *     servicio; solo aplica schema.pgsql.sql.
 *
 * Uso: php database/migrate.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/config.php';

use App\Core\Database\Connection;

$driver = (string) config('DB_DRIVER', 'mysql');
$isPostgres = $driver === 'pgsql';
$schemaFile = __DIR__ . DIRECTORY_SEPARATOR . ($isPostgres ? 'schema.pgsql.sql' : 'schema.sql');

if (! is_file($schemaFile)) {
    fwrite(STDERR, "No se encontró el esquema: {$schemaFile}\n");
    exit(1);
}

$sql = file_get_contents($schemaFile);
// Elimina líneas de comentario SQL (-- ...) para un split seguro por ';'.
$sql = preg_replace('/^\s*--.*$/m', '', $sql);

if (! $isPostgres) {
    // MySQL: crear la base de datos si no existe (conexión sin dbname).
    $host = (string) config('DB_HOST', '127.0.0.1');
    $port = (string) config('DB_PORT', '3306');
    $name = (string) config('DB_NAME', 'aula_virtual');
    $user = (string) config('DB_USER', 'root');
    $pass = (string) config('DB_PASS', '');

    try {
        $server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Base de datos '{$name}' lista.\n";
    } catch (PDOException $e) {
        fwrite(STDERR, "No se pudo crear la base de datos: " . $e->getMessage() . "\n");
        exit(1);
    }
} else {
    echo "PostgreSQL detectado: aplicando esquema sobre la base de datos existente.\n";
}

$pdo = Connection::pdo();

echo "Aplicando esquema de base de datos...\n";
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $i => $statement) {
    if ($statement === '') {
        continue;
    }
    try {
        $pdo->exec($statement);
        echo "  ✓ declaración #" . ($i + 1) . " aplicada\n";
    } catch (PDOException $e) {
        fwrite(STDERR, "  ✗ error en declaración #" . ($i + 1) . ": " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Migración completada.\n";
