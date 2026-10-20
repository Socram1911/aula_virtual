<?php
/** @var array $unit @var array $blocks @var array $students */
$typeLabels = [
    'TEXT' => 'Texto', 'IMAGE' => 'Imagen', 'VIDEO' => 'Video', 'PDF' => 'PDF',
    'LINK' => 'Enlace', 'EMBED' => 'Insertado', 'H5P' => 'H5P',
    'ACTIVITY' => 'Actividad', 'ASSESSMENT' => 'Evaluación',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">Seguimiento de contenido</h1>
        <span class="text-muted"><?= e($unit['title']) ?> · <?= e($unit['saber_name']) ?></span>
    </div>
    <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm">Volver a reportes</a>
</div>

<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="min-width:160px">Estudiante</th>
                    <?php foreach ($blocks as $b): ?>
                        <th class="text-center" title="<?= e($b['title'] ?: ($typeLabels[$b['type']] ?? $b['type'])) ?>">
                            <div class="small"><?= e($b['title'] ?: '#' . $b['position']) ?></div>
                            <span class="badge bg-secondary"><?= e($typeLabels[$b['type']] ?? $b['type']) ?></span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (! $students): ?>
                <tr><td colspan="<?= count($blocks) + 1 ?>" class="text-center text-muted py-4">Sin estudiantes inscritos en este Saber.</td></tr>
            <?php endif; ?>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= e($s['last_name'] . ', ' . $s['first_name']) ?></td>
                    <?php foreach ($blocks as $b): ?>
                        <?php
                        $p = $s['blocks'][(int) $b['id']] ?? null;
                        $status = $p['status'] ?? 'NOT_STARTED';
                        $viewed = is_array($p) && ! empty($p['viewed_at']);
                        $videoPct = is_array($p) && $p['video_percent'] !== null ? (float) $p['video_percent'] : null;
                        ?>
                        <td class="text-center">
                            <?php if ($status === 'COMPLETED'): ?>
                                <span class="badge bg-success" title="Completado: <?= e($p['completed_at'] ?? '') ?>">Completado</span>
                            <?php elseif ($viewed): ?>
                                <span class="badge bg-primary" title="Visto: <?= e($p['viewed_at'] ?? '') ?>">
                                    <?= $videoPct !== null ? 'Visto ' . (int) round($videoPct) . '%' : 'Visto' ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Sin ver</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    <strong>Leyenda:</strong>
    <span class="badge bg-success">Completado</span> el bloque se terminó (video terminado, actividad correcta o "marcar completado").
    <span class="badge bg-primary">Visto</span> el estudiante abrió/desplazó el bloque en pantalla (fecha y % de video registrados).
    <span class="badge bg-light text-muted border">Sin ver</span> aún no hay registro de visualización.
</p>
