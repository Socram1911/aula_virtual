<?php

declare(strict_types=1);

namespace App\Modules\Assessments\Models;

final class Assessment
{
    public ?int $id = null;
    public ?int $unit_id = null;
    public ?int $saber_assignment_id = null;
    public string $title = '';
    public string $description = '';
    public float $max_score = 10.0;
    public float $passing_score = 6.0;
    public ?int $max_attempts = null;
    public ?int $time_limit = null;
    public ?string $available_from = null;
    public ?string $available_until = null;
    public string $status = 'DRAFT';

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->unit_id = isset($row['unit_id']) ? (int) $row['unit_id'] : null;
        $m->saber_assignment_id = isset($row['saber_assignment_id']) ? (int) $row['saber_assignment_id'] : null;
        $m->title = (string) ($row['title'] ?? '');
        $m->description = (string) ($row['description'] ?? '');
        $m->max_score = (float) ($row['max_score'] ?? 10);
        $m->passing_score = (float) ($row['passing_score'] ?? 6);
        $m->max_attempts = isset($row['max_attempts']) ? (int) $row['max_attempts'] : null;
        $m->time_limit = isset($row['time_limit']) ? (int) $row['time_limit'] : null;
        $m->available_from = isset($row['available_from']) && $row['available_from'] !== '' ? (string) $row['available_from'] : null;
        $m->available_until = isset($row['available_until']) && $row['available_until'] !== '' ? (string) $row['available_until'] : null;
        $m->status = (string) ($row['status'] ?? 'DRAFT');
        return $m;
    }
}
