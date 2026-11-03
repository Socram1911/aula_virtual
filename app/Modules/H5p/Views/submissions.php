<?php
/** @var \App\Modules\Content\Models\ContentBlock $block @var int $assignmentId @var array $submissions @var string $title @var float $maxValue */
$pending = 0;
$released = 0;
foreach ($submissions as $s) {
    $s['status'] === 'RELEASED' ? $released++ : $pending++;
}
$maxValue = (float) $maxValue;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e($title) ?></h1>
    <a href="<?= url('/saber-assignments/' . $assignmentId) ?>" class="btn btn-outline-secondary btn-sm">Volver a la asignación</a>
</div>

<div class="card card-av mb-3">
    <div class="card-body">
        <p class="mb-1">Entregas: <strong><?= $pending ?></strong> pendientes · <strong><?= $released ?></strong> liberadas. Valor de la evaluación: <strong><?= e(number_format($maxValue, 2)) ?> puntos</strong>.</p>
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
                <tr><th>Estudiante</th><th>Enviado</th><th>Tipo</th><th>Respuesta</th><th>Resultado</th><th>Nota (máx <?= e(number_format($maxValue, 2)) ?>)</th><th>Estado</th><th class="text-end">Acción</th></tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <?php
                    $stateAnswerText = $s['answer_text'] !== null && $s['answer_text'] !== ''
                        ? $s['answer_text']
                        : null;
                    // Nota en puntos: la guardada, o la sugerida (opción múltiple).
                    $gradeVal = $s['grade'] !== null ? (float) $s['grade'] : null;
                    if ($gradeVal === null && ! empty($s['auto_graded']) && (float) $s['max_score'] > 0) {
                        $gradeVal = round(((float) $s['score'] / (float) $s['max_score']) * $maxValue, 2);
                    }
                ?>
                <tr>
                    <td><?= e($s['last_name'] . ', ' . $s['first_name']) ?><div class="small text-muted"><?= e($s['email']) ?></div></td>
                    <td class="small"><?= e($s['submitted_at'] ?? '—') ?></td>
                    <td>
                        <?php if (! empty($s['auto_graded'])): ?>
                            <span class="badge bg-info text-dark">Auto</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Manual</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                            $friendly = $s['friendly'] ?? [];
                            $hasFriendly = $friendly !== [];
                            $hasStates = ($s['states'] ?? []) !== [];
                        ?>
                        <?php if ($hasFriendly || $stateAnswerText !== null || $hasStates): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary view-answers"
                                    data-sid="<?= (int) $s['id'] ?>"
                                    data-student="<?= e($s['last_name'] . ', ' . $s['first_name']) ?>">
                                Ver respuestas
                            </button>
                            <div class="d-none" id="answers-<?= (int) $s['id'] ?>">
                                <?php if ($hasFriendly): ?>
                                    <ol class="mb-0 ps-3">
                                    <?php foreach ($friendly as $line): ?>
                                        <li class="mb-2">
                                            <?php if ($line['question'] !== ''): ?>
                                                <div class="fw-semibold"><?= e($line['question']) ?></div>
                                            <?php endif; ?>
                                            <div><?= nl2br(e($line['answer'])) ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                    </ol>
                                <?php endif; ?>

                                <?php if (! $hasFriendly && $stateAnswerText !== null): ?>
                                    <strong>Texto enviado:</strong>
                                    <div class="mb-2"><?= nl2br(e($stateAnswerText)) ?></div>
                                <?php endif; ?>

                                <?php if (! $hasFriendly && $hasStates): ?>
                                    <?php foreach ($s['states'] as $st): ?>
                                        <?php
                                            $stAnswer = $st['answer_text'] ?? null;
                                            $stPretty = null;
                                            if (! empty($st['state'])) {
                                                $decoded = json_decode((string) $st['state'], true);
                                                if (is_array($decoded)) {
                                                    $stPretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                                }
                                            }
                                        ?>
                                        <?php if ($stAnswer !== null || $stPretty !== null): ?>
                                            <div class="border-top pt-2 mt-2">
                                                <span class="text-muted">Pregunta #<?= (int) $st['sub_content_id'] ?>:</span>
                                                <?php if ($stAnswer !== null && $stAnswer !== ''): ?>
                                                    <div><?= nl2br(e($stAnswer)) ?></div>
                                                <?php endif; ?>
                                                <?php if ($stPretty !== null): ?>
                                                    <pre class="mb-0 mt-1"><?= e($stPretty) ?></pre>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">Sin datos capturados.</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (! empty($s['auto_graded'])): ?>
                            <span class="text-muted small"><?= e(number_format((float) $s['score'], 2)) ?> / <?= e(number_format((float) $s['max_score'], 2)) ?></span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($gradeVal !== null): ?>
                            <strong><?= e(number_format($gradeVal, 2)) ?></strong>
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
                        <form method="post" action="<?= url('/api/h5p/submission/' . (int) $s['id'] . '/grade') ?>" class="d-flex gap-1 justify-content-end align-items-center">
                            <?= csrf_field() ?>
                            <input type="hidden" name="max" value="<?= e(number_format($maxValue, 2)) ?>">
                            <input type="number" step="0.01" min="0" max="<?= e(number_format($maxValue, 2)) ?>" name="grade" class="form-control form-control-sm" style="width:90px"
                                   value="<?= $gradeVal !== null ? e(number_format($gradeVal, 2)) : '' ?>" placeholder="Nota">
                            <button class="btn btn-sm btn-primary">Guardar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $submissions): ?><tr><td colspan="8" class="text-muted text-center py-4">Sin entregas todavía.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de respuestas del estudiante -->
<div class="modal fade" id="answers-modal" tabindex="-1" aria-labelledby="answers-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="answers-modal-title">Respuestas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body small" id="answers-modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.view-answers');
    if (!btn) { return; }
    var content = document.getElementById('answers-' + btn.dataset.sid);
    document.getElementById('answers-modal-title').textContent = 'Respuestas de ' + btn.dataset.student;
    document.getElementById('answers-modal-body').innerHTML = content ? content.innerHTML : '<p class="text-muted mb-0">Sin datos capturados.</p>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('answers-modal')).show();
});
</script>
