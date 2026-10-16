<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

final class User
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_PENDING = 'PENDING';

    public ?int $id = null;
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $status = self::STATUS_ACTIVE;
    /** @var string[] */
    public array $roles = [];

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->first_name = (string) ($row['first_name'] ?? '');
        $m->last_name = (string) ($row['last_name'] ?? '');
        $m->email = (string) ($row['email'] ?? '');
        $m->status = (string) ($row['status'] ?? self::STATUS_ACTIVE);
        $m->roles = isset($row['roles']) ? (array) $row['roles'] : [];
        return $m;
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
