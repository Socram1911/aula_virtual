<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Models;

final class Saber
{
    public ?int $id = null;
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public int $track_id = 0;
    public string $unlock_mode = 'FREE';
    public string $status = 'ACTIVE';
    public string $track_name = '';

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->code = (string) ($row['code'] ?? '');
        $m->name = (string) ($row['name'] ?? '');
        $m->description = (string) ($row['description'] ?? '');
        $m->track_id = (int) ($row['track_id'] ?? 0);
        $m->unlock_mode = (string) ($row['unlock_mode'] ?? 'FREE');
        $m->status = (string) ($row['status'] ?? 'ACTIVE');
        $m->track_name = (string) ($row['track_name'] ?? '');
        return $m;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'track_id' => $this->track_id,
            'unlock_mode' => $this->unlock_mode,
            'status' => $this->status,
        ];
    }
}
