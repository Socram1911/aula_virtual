<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

/**
 * Excepción HTTP con código de estado.
 */
class HttpException extends Exception
{
    public function __construct(
        private int $statusCode = 500,
        string $message = '',
        private array $details = []
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function details(): array
    {
        return $this->details;
    }

    public static function notFound(string $message = 'Recurso no encontrado.'): self
    {
        return new self(404, $message);
    }

    public static function unauthorized(string $message = 'No autorizado.'): self
    {
        return new self(401, $message);
    }

    public static function forbidden(string $message = 'Acceso denegado.'): self
    {
        return new self(403, $message);
    }

    public static function conflict(string $message = 'Conflicto con el estado actual.'): self
    {
        return new self(409, $message);
    }

    public static function unprocessable(string $message = 'Datos inválidos.', array $details = []): self
    {
        return new self(422, $message, $details);
    }
}
