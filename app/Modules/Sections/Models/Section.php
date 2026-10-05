<?php

declare(strict_types=1);

namespace App\Modules\Sections\Models;

final class Section
{
    public ?int $id = null;
    public int $academic_period_id = 0;
    public int $track_id = 0;
    public int $number = 0;
    public string $name = '';
    public string $description = '';
    public string $status = 'ACTIVE';

    // Campos de conveniencia (joins).
    public string $period_name = '';
    public string $track_name = '';

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->academic_period_id = (int) ($row['academic_period_id'] ?? 0);
        $m->track_id = (int) ($row['track_id'] ?? 0);
        $m->number = (int) ($row['number'] ?? 0);
        $m->name = (string) ($row['name'] ?? '');
        $m->description = (string) ($row['description'] ?? '');
        $m->status = (string) ($row['status'] ?? 'ACTIVE');
        $m->period_name = (string) ($row['period_name'] ?? '');
        $m->track_name = (string) ($row['track_name'] ?? '');
        return $m;
    }

    /** Etiqueta visible: "Sección 20". */
    public function label(): string
    {
        return 'Sección ' . $this->number;
    }

    public function toArray(): array
    {
        return [
            'academic_period_id' => $this->academic_period_id,
            'track_id' => $this->track_id,
            'number' => $this->number,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
