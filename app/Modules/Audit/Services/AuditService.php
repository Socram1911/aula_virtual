<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use App\Core\Auth\Auth;
use App\Core\Database\Connection;
use App\Core\Request\Request;

/**
 * Registro de auditoría (quién hizo qué, sobre qué entidad, cuándo).
 */
final class AuditService
{
    public static function log(
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldData = null,
        ?array $newData = null
    ): void {
        Connection::run(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_data, new_data, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :old_data, :new_data, :ip)',
            [
                ':user_id' => Auth::id(),
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':old_data' => $oldData === null ? null : json_encode($oldData, JSON_UNESCAPED_UNICODE),
                ':new_data' => $newData === null ? null : json_encode($newData, JSON_UNESCAPED_UNICODE),
                ':ip' => Request::capture()->ip(),
            ]
        );
    }
}
