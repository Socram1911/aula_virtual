<?php /** @var array $assignments */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Asignaciones de Saber</h1>
    <a href="<?= url('/saber-assignments/create') ?>" class="btn btn-primary btn-sm">+ Nueva asignación</a>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Saber</th><th>Sección</th><th>Período</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($assignments as $a): ?>
                <tr>
                    <td><?= e($a->saber_name) ?></td>
                    <td><?= e($a->sectionLabel()) ?></td>
                    <td><?= e($a->period_name) ?></td>
                    <td><span class="badge <?= $a->status === 'PUBLISHED' ? 'bg-success' : 'bg-secondary' ?>"><?= e($a->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/saber-assignments/' . $a->id) ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                        <a href="<?= url('/saber-assignments/' . $a->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <?php if ($a->status === 'DRAFT'): ?>
                        <form method="post" action="<?= url('/saber-assignments/' . $a->id . '/status') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="PUBLISHED">
                            <button class="btn btn-sm btn-outline-success">Publicar</button>
                        </form>
                        <?php endif; ?>
                        <form method="post" action="<?= url('/saber-assignments/' . $a->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta asignación?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $assignments): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay asignaciones.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
