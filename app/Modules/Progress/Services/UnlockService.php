<?php

declare(strict_types=1);

namespace App\Modules\Progress\Services;

use App\Core\Database\Connection;

/**
 * Reglas de desbloqueo de unidades (FREE / SEQUENTIAL).
 */
final class UnlockService
{
    /** Devuelve true si el estudiante puede acceder a la unidad indicada. */
    public function isUnlocked(int $studentId, int $saberId, int $unitId, array $orderedUnits): bool
    {
        $row = Connection::fetch('SELECT unlock_mode FROM sabers WHERE id = :id', [':id' => $saberId]);
        $mode = (string) ($row['unlock_mode'] ?? 'FREE');
        if ($mode !== 'SEQUENTIAL') {
            return true;
        }

        // Encontrar la unidad anterior en orden.
        $previous = null;
        foreach ($orderedUnits as $unit) {
            if ((int) $unit['id'] === $unitId) {
                break;
            }
            $previous = $unit;
        }
        if ($previous === null) {
            return true; // primera unidad, siempre accesible.
        }

        $progress = Connection::fetch(
            'SELECT status FROM student_unit_progress WHERE student_id = :s AND unit_id = :u',
            [':s' => $studentId, ':u' => (int) $previous['id']]
        );
        return ($progress['status'] ?? '') === 'COMPLETED';
    }

    /** Unidades visibles/desbloqueadas para el estudiante, en orden. */
    public function unlockedUnits(int $studentId, int $saberId, array $orderedUnits): array
    {
        $result = [];
        foreach ($orderedUnits as $unit) {
            $result[] = $unit + [
                'unlocked' => $this->isUnlocked($studentId, $saberId, (int) $unit['id'], $orderedUnits),
            ];
        }
        return $result;
    }
}
