<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Models;

final class SaberAssignment
{
    public ?int $id = null;
    public int $saber_id = 0;
    public int $section_id = 0;
    public int $academic_period_id = 0;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public string $status = 'DRAFT';

    // Conveniencia (joins).
    public string $saber_name = '';
    public string $section_number = '0';
    public string $period_name = '';
    public string $track_name = '';
    public float $progress = 0.0;

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->saber_id = (int) ($row['saber_id'] ?? 0);
        $m->section_id = (int) ($row['section_id'] ?? 0);
        $m->academic_period_id = (int) ($row['academic_period_id'] ?? 0);
        $m->start_date = isset($row['start_date']) && $row['start_date'] !== '' ? (string) $row['start_date'] : null;
        $m->end_date = isset($row['end_date']) && $row['end_date'] !== '' ? (string) $row['end_date'] : null;
        $m->status = (string) ($row['status'] ?? 'DRAFT');
        $m->saber_name = (string) ($row['saber_name'] ?? '');
        $m->section_number = (string) ($row['section_number'] ?? '0');
        $m->period_name = (string) ($row['period_name'] ?? '');
        $m->track_name = (string) ($row['track_name'] ?? '');
        return $m;
    }

    public function sectionLabel(): string
    {
        return 'Sección ' . $this->section_number;
    }
}
