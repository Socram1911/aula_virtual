<?php
/** @var \App\Modules\Content\Models\ContentBlock $block @var int $assignmentId @var array $submissions @var string $title */
$to20 = fn ($pct) => number_format(((float) $pct) / 5, 2, '.', '');
$pending = 0;
$released = 0;
foreach ($submissions as $s) {
    $s['status'] === 'RELEASED' ? $released++ : $pending++;
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e($title) ?></h1>
    <a href="<?= url('/saber-assignments/' . $assignmentId) ?>" class="btn btn-outline-secondary btn-sm">Volver a la asignación</a>
</div>

<div class="card card-av mb-3">
    <div class="card-body">
        <p class="mb-1">Entregas: <strong><?= $pending ?></strong> pendientes · <strong><?= $released ?></strong> liberadas.</p>
        <?php if ($submissions): ?>
        <form method="post" action="<?= url('/saber-assignments/' . $assignmentId . '/h5p/' . (int) $block->id . '/release') ?>"
              onsubmit="return confirm('¿Liberar los resultados para todos los estudiantes?');">
            <?= csrf_field() ?>
            <button class="btn btn-success btn-sm">Liberar resultados</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Estudiante</th><th>Enviado</th><th>Tipo</th><th>Respuesta</th><th>Nota (20)</th><th>Estado</th><th class="text-end">Acción</th></tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <?php
                    $answerText = $s['answer_text'] !== null && $s['answer_text'] !== ''
                        ? $s['answer_text']
                        : ($s['state_answer'] ?? null);
                    $statePretty = null;
                    if (! empty($s['state_json'])) {
                        $decoded = json_decode((string) $s['state_json'], true);
                        if (is_array($decoded)) {
                            $statePretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }
                ?>
                <tr>
                    <td><?= e($s['last_name'] . ', ' . $s['first_name']) ?><div class="small text-muted"><?= e($s['email']) ?></div></td>
                    <td class="small"><?= e($s['submitted_at'] ?? '—') ?></td>
                    <td>
                        <?php if (! empty($s['auto_graded'])): ?>
                            <span class="badge bg-info text-dark">Auto</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Manual (ensayo)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($answerText !== null || $statePretty !== null): ?>
                            <details>
                                <summary class="small text-primary">Ver respuesta</summary>
                                <div class="border rounded p-2 mt-1 small" style="max-height:240px;overflow:auto">
                                    <?php if ($answerText !== null && $answerText !== ''): ?>
                                        <strong>Texto:</strong>
                                        <div class="mb-2"><?= nl2br(e($answerText)) ?></div>
                                    <?php endif; ?>
                                    <?php if ($statePretty !== null): ?>
                                        <strong>Estado completo (H5P):</strong>
                                        <pre class="mb-0 mt-1"><?= e($statePretty) ?></pre>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php else: ?>
                            <span class="text-muted small">Sin datos capturados.</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['percentage'] !== null): ?>
                            <strong><?= e($to20($s['percentage'])) ?></strong>
                            <span class="small text-muted">(<?= e(number_format((float) $s['percentage'], 2)) ?>%)</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['status'] === 'RELEASED'): ?>
                            <span class="badge bg-success">Liberada</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if (empty($s['auto_graded'])): ?>
                            <form method="post" action="<?= url('/api/h5p/submission/' . (int) $s['id'] . '/grade') ?>" class="d-flex gap-1 justify-content-end align-items-center">
                                <?= csrf_field() ?>
                                <input type="hidden" name="max_score" value="20">
                                <input type="number" step="0.01" min="0" max="20" name="score" class="form-control form-control-sm" style="width:80px"
                                       value="<?= $s['score'] !== null ? e((string) $s['score']) : '' ?>" placeholder="Nota /20">
                                <button class="btn btn-sm btn-primary">Guardar</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $submissions): ?><tr><td colspan="7" class="text-muted text-center py-4">Sin entregas todavía.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
