<?php
/** @var \App\Modules\Sabers\Models\Unit $unit @var array $blocks */
$blockView = dirname(__DIR__, 4) . '/app/Modules/Content/Views/_block.php';
$isStudent = \App\Core\Auth\Auth::hasRole('STUDENT');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e($unit->title) ?></h1>
    <a href="<?= url('/sabers/' . $unit->saber_id) ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<?php if ($unit->description): ?><p class="text-muted"><?= e($unit->description) ?></p><?php endif; ?>

<?php if (! $blocks): ?>
    <div class="alert alert-info">Esta unidad no tiene contenido publicado.</div>
<?php endif; ?>

<?php foreach ($blocks as $b): ?>
    <?php $isEvaluableH5p = $b->type === 'H5P' && (($b->settingsArray()['evaluable'] ?? false) === true); ?>
    <div class="card card-av mb-3" <?= $isStudent ? 'data-track-block="' . (int) $b->id . '"' : '' ?>>
        <div class="card-body">
            <?php if ($b->title): ?><h2 class="h6"><?= e($b->title) ?></h2><?php endif; ?>
            <?php $block = $b; include $blockView; ?>
            <?php if ($isStudent && $b->type !== 'ACTIVITY' && $b->type !== 'ASSESSMENT' && $b->type !== 'VIDEO' && ! $isEvaluableH5p): ?>
                <button type="button" class="btn btn-sm btn-outline-success mt-2 mark-complete" data-block-id="<?= (int) $b->id ?>">✓ Marcar como completado</button>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php if ($isStudent): ?>
<script>
document.addEventListener('click', async function (ev) {
    var btn = ev.target.closest('.mark-complete');
    if (!btn) return;
    btn.disabled = true;
    try {
        var r = await window.AV.post('/api/progress/block', { block_id: parseInt(btn.dataset.blockId, 10) });
        btn.textContent = '✓ Completado';
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
    } catch (err) {
        btn.textContent = 'Error: ' + err.message;
        btn.disabled = false;
    }
});
</script>
<?php endif; ?>
