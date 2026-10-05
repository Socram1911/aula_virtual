<?php
/** @var \App\Modules\Sabers\Models\Saber $saber @var array $units @var bool $isStudent */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1"><?= e($saber->name) ?></h1>
        <span class="text-muted"><?= e($saber->code) ?> · <?= e($saber->track_name) ?></span>
    </div>
    <?php if (! $isStudent): ?>
        <a href="<?= url('/sabers/' . $saber->id . '/units') ?>" class="btn btn-outline-secondary btn-sm">Gestionar</a>
    <?php endif; ?>
</div>

<?php if ($saber->description): ?><p class="text-muted"><?= e($saber->description) ?></p><?php endif; ?>

<div class="list-group">
    <?php foreach ($units as $u): ?>
        <?php $unlocked = ! empty($u['unlocked']); ?>
        <div class="list-group-item d-flex justify-content-between align-items-center <?= $unlocked ? '' : 'disabled opacity-50' ?>">
            <div>
                <span class="text-muted small">Unidad <?= (int) $u['position'] ?></span>
                <div><?= e($u['title']) ?></div>
            </div>
            <?php if ($unlocked): ?>
                <a href="<?= url('/units/' . $u['id']) ?>" class="btn btn-sm btn-outline-primary">Abrir</a>
            <?php else: ?>
                <span class="badge bg-secondary">Bloqueada</span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (! $units): ?><div class="list-group-item text-muted">Este saber no tiene unidades.</div><?php endif; ?>
</div>
