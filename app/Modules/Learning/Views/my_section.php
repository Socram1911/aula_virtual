<?php /** @var array $sections */ ?>
<h1 class="h4 mb-3">Mi Sección</h1>
<?php if (! $sections): ?>
    <div class="alert alert-info">No está inscrito en ninguna sección.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($sections as $s): ?>
            <div class="col-md-6">
                <div class="card card-av">
                    <div class="card-body">
                        <h2 class="h6"><?= e($s['name'] ?: ('Sección ' . $s['number'])) ?></h2>
                        <p class="mb-1 text-muted"><?= e($s['period_name']) ?> · <?= e($s['track_name']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
