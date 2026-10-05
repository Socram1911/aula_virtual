<?php /** @var array $assessments */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Evaluaciones</h1>
    <a href="<?= url('/assessments/create') ?>" class="btn btn-primary btn-sm">+ Nueva evaluación</a>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Título</th><th>Puntaje</th><th>Aprobación</th><th>Intentos</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($assessments as $a): ?>
                <tr>
                    <td><?= e($a->title) ?></td>
                    <td><?= e($a->max_score) ?></td>
                    <td><?= e($a->passing_score) ?></td>
                    <td><?= $a->max_attempts !== null ? (int) $a->max_attempts : '∞' ?></td>
                    <td><span class="badge <?= $a->status === 'PUBLISHED' ? 'bg-success' : 'bg-secondary' ?>"><?= e($a->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/assessments/' . $a->id . '/questions') ?>" class="btn btn-sm btn-outline-primary">Preguntas</a>
                        <a href="<?= url('/assessments/' . $a->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <?php if ($a->status === 'DRAFT'): ?>
                        <form method="post" action="<?= url('/assessments/' . $a->id . '/status') ?>" class="d-inline">
                            <?= csrf_field() ?><input type="hidden" name="status" value="PUBLISHED">
                            <button class="btn btn-sm btn-outline-success">Publicar</button>
                        </form>
                        <?php endif; ?>
                        <form method="post" action="<?= url('/assessments/' . $a->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta evaluación?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $assessments): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay evaluaciones.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
