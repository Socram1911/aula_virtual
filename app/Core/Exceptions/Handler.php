<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Throwable;

/**
 * Manejo global de errores y excepciones.
 */
final class Handler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (! (error_reporting() & $severity)) {
            return false;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        if ($e instanceof HttpException) {
            $status = $e->statusCode();
        } else {
            $status = 500;
        }

        http_response_code($status);

        $isJson = self::wantsJson();
        $debug = (bool) config('APP_DEBUG', false);

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            $payload = [
                'error' => [
                    'code' => (string) $status,
                    'message' => $status === 500 && ! $debug ? 'Error interno del servidor.' : $e->getMessage(),
                ],
            ];
            if ($e instanceof HttpException && $e->details()) {
                $payload['error']['details'] = $e->details();
            }
            echo json_encode($payload);
            return;
        }

        if ($status === 500 && ! $debug) {
            $message = 'Ocurrió un error inesperado. Intente nuevamente.';
        } else {
            $message = $e->getMessage();
        }

        $title = self::titleFor($status);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $title . '</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            . '</head><body class="bg-light"><div class="container py-5">'
            . '<div class="card shadow-sm"><div class="card-body text-center">'
            . '<h1 class="display-4">' . $status . '</h1><p class="fs-5">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<a href="' . url('/') . '" class="btn btn-primary">Volver al inicio</a>'
            . '</div></div></div></body></html>';
    }

    private static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $path = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($accept, 'application/json') || str_contains($path, '/api/');
    }

    private static function titleFor(int $status): string
    {
        return match ($status) {
            401 => 'No autorizado',
            403 => 'Acceso denegado',
            404 => 'No encontrado',
            409 => 'Conflicto',
            422 => 'Datos inválidos',
            default => 'Error',
        };
    }
}
