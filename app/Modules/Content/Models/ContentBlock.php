<?php

declare(strict_types=1);

namespace App\Modules\Content\Models;

final class ContentBlock
{
    public ?int $id = null;
    public int $unit_id = 0;
    public string $type = 'TEXT';
    public int $position = 0;
    public string $title = '';
    public string $content = '';
    public ?string $settings = null;
    public string $status = 'ACTIVE';

    public const TYPES = ['TEXT', 'IMAGE', 'VIDEO', 'PDF', 'LINK', 'EMBED', 'ACTIVITY', 'ASSESSMENT', 'H5P'];

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->unit_id = (int) ($row['unit_id'] ?? 0);
        $m->type = (string) ($row['type'] ?? 'TEXT');
        $m->position = (int) ($row['position'] ?? 0);
        $m->title = (string) ($row['title'] ?? '');
        $m->content = (string) ($row['content'] ?? '');
        $m->settings = isset($row['settings']) ? (string) $row['settings'] : null;
        $m->status = (string) ($row['status'] ?? 'ACTIVE');
        return $m;
    }

    /** Configuración decodificada como arreglo. */
    public function settingsArray(): array
    {
        if ($this->settings === null || $this->settings === '') {
            return [];
        }
        $decoded = json_decode($this->settings, true);
        return is_array($decoded) ? $decoded : [];
    }
}
