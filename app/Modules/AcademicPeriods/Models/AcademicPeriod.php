<?php

declare(strict_types=1);

namespace App\Modules\AcademicPeriods\Models;

final class AcademicPeriod
{
    public ?int $id = null;
    public string $name = '';
    public string $code = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $status = 'DRAFT';

    public const STATUSES = ['DRAFT', 'ACTIVE', 'CLOSED', 'ARCHIVED'];

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->name = (string) ($row['name'] ?? '');
        $m->code = (string) ($row['code'] ?? '');
        $m->start_date = (string) ($row['start_date'] ?? '');
        $m->end_date = (string) ($row['end_date'] ?? '');
        $m->status = (string) ($row['status'] ?? 'DRAFT');
        return $m;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
        ];
    }

    public function label(): string
    {
        return $this->name;
    }
}
