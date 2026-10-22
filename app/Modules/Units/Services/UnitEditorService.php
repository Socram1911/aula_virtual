<?php

declare(strict_types=1);

namespace App\Modules\Units\Services;

use App\Core\Exceptions\HttpException;
use App\Core\Html\HtmlSanitizer;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Content\Repositories\ContentBlockRepository;

final class UnitEditorService
{
    private ContentBlockRepository $repository;

    public function __construct()
    {
        $this->repository = new ContentBlockRepository();
    }

    public function blocks(int $unitId): array
    {
        return $this->repository->blocks($unitId);
    }

    public function findBlock(int $id): ContentBlock
    {
        $block = $this->repository->find($id);
        if ($block === null) {
            throw HttpException::notFound('Bloque de contenido no encontrado.');
        }
        return $block;
    }

    public function addBlock(int $unitId, array $input): ContentBlock
    {
        $type = strtoupper((string) ($input['type'] ?? 'TEXT'));
        if (! in_array($type, ContentBlock::TYPES, true)) {
            throw HttpException::unprocessable('Tipo de bloque inválido.');
        }

        $title = trim((string) ($input['title'] ?? ''));
        $content = (string) ($input['content'] ?? '');
        $settings = $input['settings'] ?? null;

        // Los bloques de texto guardan HTML enriquecido; se sanitiza antes de persistir.
        if ($type === 'TEXT') {
            $content = HtmlSanitizer::clean($content);
        }

        // Validación específica por tipo.
        if ($type === 'VIDEO' && $content !== '') {
            $content = self::normalizeYoutubeId($content);
            if ($content === null) {
                throw HttpException::unprocessable('El identificador de video de YouTube es inválido.');
            }
        }
        if ($type === 'LINK' && $content !== '' && ! filter_var($content, FILTER_VALIDATE_URL)) {
            throw HttpException::unprocessable('La URL del enlace es inválida.');
        }

        $position = $this->repository->nextPosition($unitId);
        $id = $this->repository->create([
            ':unit_id' => $unitId,
            ':type' => $type,
            ':position' => $position,
            ':title' => $title,
            ':content' => $content,
            ':settings' => $settings,
            ':status' => 'ACTIVE',
        ]);

        AuditService::log('crear Bloque', 'ContentBlock', $id, null, ['type' => $type, 'unit_id' => $unitId]);
        return $this->findBlock($id);
    }

    public function updateBlock(int $id, array $input): ContentBlock
    {
        $block = $this->findBlock($id);
        $title = trim((string) ($input['title'] ?? $block->title));
        $content = (string) ($input['content'] ?? $block->content);
        $settings = $input['settings'] ?? $block->settings;

        if ($block->type === 'TEXT') {
            $content = HtmlSanitizer::clean($content);
        }

        if ($block->type === 'VIDEO' && $content !== '') {
            $content = self::normalizeYoutubeId($content);
            if ($content === null) {
                throw HttpException::unprocessable('El identificador de video de YouTube es inválido.');
            }
        }
        if ($block->type === 'LINK' && $content !== '' && ! filter_var($content, FILTER_VALIDATE_URL)) {
            throw HttpException::unprocessable('La URL del enlace es inválida.');
        }

        $this->repository->update($id, [
            ':title' => $title,
            ':content' => $content,
            ':settings' => $settings,
            ':status' => (string) ($input['status'] ?? $block->status),
        ]);

        AuditService::log('modificar Bloque', 'ContentBlock', $id, ['type' => $block->type], ['type' => $block->type]);
        return $this->findBlock($id);
    }

    public function duplicateBlock(int $id): ContentBlock
    {
        $block = $this->findBlock($id);
        $position = $this->repository->nextPosition($block->unit_id);
        $newId = $this->repository->create([
            ':unit_id' => $block->unit_id,
            ':type' => $block->type,
            ':position' => $position,
            ':title' => $block->title . ' (copia)',
            ':content' => $block->content,
            ':settings' => $block->settings,
            ':status' => $block->status,
        ]);
        return $this->findBlock($newId);
    }

    public function deleteBlock(int $id): void
    {
        $block = $this->findBlock($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Bloque', 'ContentBlock', $id, ['type' => $block->type], null);
    }

    /** Mueve un bloque arriba o abajo intercambiando posiciones. */
    public function moveBlock(int $id, string $direction): void
    {
        $block = $this->findBlock($id);
        $siblings = $this->repository->blocks($block->unit_id);
        $target = null;
        if ($direction === 'up') {
            for ($i = count($siblings) - 1; $i > 0; $i--) {
                if ($siblings[$i]->id === $block->id) {
                    $target = $siblings[$i - 1];
                    break;
                }
            }
        } else {
            for ($i = 0; $i < count($siblings) - 1; $i++) {
                if ($siblings[$i]->id === $block->id) {
                    $target = $siblings[$i + 1];
                    break;
                }
            }
        }
        if ($target !== null) {
            $tmp = $block->position;
            $this->repository->updatePosition($block->id, $target->position);
            $this->repository->updatePosition($target->id, $tmp);
        }
    }

    public function setBlockStatus(int $id, string $status): void
    {
        $block = $this->findBlock($id);
        $status = $status === 'ACTIVE' ? 'ACTIVE' : 'INACTIVE';
        $this->repository->updateStatus($id, $status);
        AuditService::log('activar Bloque', 'ContentBlock', $id, ['status' => $block->status], ['status' => $status]);
    }

    /**
     * Extrae el id de un video de YouTube a partir de un id directo o de una URL
     * (watch?v=, youtu.be, embed, shorts, live). Devuelve null si no es válido.
     */
    private static function normalizeYoutubeId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]{6,20}$/', $value)) {
            return $value;
        }

        if (preg_match(
            '~(?:youtube\.com/(?:watch\?(?:[^&\s]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~i',
            $value,
            $m
        )) {
            return $m[1];
        }

        return null;
    }
}
