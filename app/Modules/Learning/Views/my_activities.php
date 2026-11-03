<?php
/** @var array $pending @var array $grades @var string $title */
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>

<div class="card card-av mb-4">
    <div class="card-body">
        <h2 class="h6">Evaluaciones pendientes</h2>
        <?php if (! $pending): ?>
            <p class="text-muted small mb-0">No tiene evaluaciones pendientes.</p>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($pending as $p): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= e($p['title'] ?: '(sin título)') ?></strong>
                            <div class="small text-muted"><?= e($p['saber_name']) ?> · <?= e($p['unit_title']) ?></div>
                        </div>
                        <a href="<?= url('/units/' . (int) $p['unit_id']) ?>" class="btn btn-sm btn-primary">Ir a la unidad</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-av">
    <div class="card-body">
        <h2 class="h6">Mis notas</h2>
        <?php if (! $grades): ?>
            <p class="text-muted small mb-0">Aún no tiene notas publicadas.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Saber</th><th>Evaluación</th><th>Nota</th><th>Fecha</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($grades as $g): ?>
                        <tr>
                            <td><?= e($g['saber_name']) ?></td>
                            <td>
                                <?= e($g['block_title'] ?: '(sin título)') ?>
                                <?php if (! empty($g['unit_title'])): ?><div class="small text-muted"><?= e($g['unit_title']) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e(number_format((float) $g['grade'], 2)) ?></strong>
                                <?php if ($g['max_value'] !== null && (float) $g['max_value'] > 0): ?>
                                    <span class="text-muted small">/ <?= e(number_format((float) $g['max_value'], 2)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= e($g['graded_at'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

