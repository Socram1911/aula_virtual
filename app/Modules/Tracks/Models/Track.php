<?php

declare(strict_types=1);

namespace App\Modules\Tracks\Models;

final class Track
{
    public ?int $id = null;
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public int $position = 0;
    public string $status = 'ACTIVE';

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->code = (string) ($row['code'] ?? '');
        $m->name = (string) ($row['name'] ?? '');
        $m->description = (string) ($row['description'] ?? '');
        $m->position = (int) ($row['position'] ?? 0);
        $m->status = (string) ($row['status'] ?? 'ACTIVE');
        return $m;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->position,
            'status' => $this->status,
        ];
    }
}
