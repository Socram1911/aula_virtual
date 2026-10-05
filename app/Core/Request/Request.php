<?php

declare(strict_types=1);

namespace App\Core\Request;

/**
 * Envoltura de la petición HTTP.
 */
final class Request
{
    private array $query;
    private array $body;
    private array $server;
    private array $files;

    public function __construct(array $query, array $body, array $server, array $files)
    {
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /** Ruta sin query string y sin el subdirectorio base de la aplicación. */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $raw = parse_url($uri, PHP_URL_PATH);
        $path = is_string($raw) && $raw !== '' ? '/' . ltrim($raw, '/') : '/';

        // Solo se descuenta el subdirectorio base cuando SCRIPT_NAME apunta al
        // front controller (index.php). De lo contrario (p. ej. servidor embebido
        // de PHP sirviendo archivos estáticos), se conserva la ruta tal cual.
        $script = str_replace('\\', '/', $this->server['SCRIPT_NAME'] ?? '');
        if ($script !== '' && str_ends_with($script, '/index.php')) {
            $baseDir = rtrim(str_replace('\\', '/', dirname($script)), '/');
            if ($baseDir !== '' && $baseDir !== '/' && str_starts_with($path, $baseDir . '/')) {
                $path = substr($path, strlen($baseDir));
            }
            if (str_ends_with($path, '/index.php')) {
                $path = substr($path, 0, -strlen('/index.php'));
            }
        }

        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    public function all(): array
    {
        return $this->body;
    }

    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->body)) {
                $result[$key] = $this->body[$key];
            }
        }
        return $result;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    /** Cuerpo JSON decodificado (para /api). */
    public function json(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '';
    }
}
