<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Models;

final class Unit
{
    public ?int $id = null;
    public int $saber_id = 0;
    public string $title = '';
    public string $description = '';
    public int $position = 0;
    public string $status = 'DRAFT';

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->saber_id = (int) ($row['saber_id'] ?? 0);
        $m->title = (string) ($row['title'] ?? '');
        $m->description = (string) ($row['description'] ?? '');
        $m->position = (int) ($row['position'] ?? 0);
        $m->status = (string) ($row['status'] ?? 'DRAFT');
        return $m;
    }

    public function toArray(): array
    {
        return [
            'saber_id' => $this->saber_id,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'status' => $this->status,
        ];
    }
}
