<?php
/** @var \App\Modules\Sabers\Models\Saber $saber @var array $units */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1"><?= e($saber->name) ?></h1>
        <span class="text-muted"><?= e($saber->code) ?> · Unidades</span>
    </div>
    <div>
        <a href="<?= url('/sabers') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
        <a href="<?= url('/sabers/' . $saber->id . '/units/create') ?>" class="btn btn-primary btn-sm">+ Nueva unidad</a>
    </div>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Orden</th><th>Título</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($units as $u): ?>
                <tr>
                    <td><?= (int) $u->position ?></td>
                    <td><?= e($u->title) ?></td>
                    <td><span class="badge <?= $u->status === 'PUBLISHED' ? 'bg-success' : 'bg-secondary' ?>"><?= e($u->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/units/' . $u->id . '/edit') ?>" class="btn btn-sm btn-outline-primary">Contenido</a>
                        <a href="<?= url('/reports/unit/' . $u->id) ?>" class="btn btn-sm btn-outline-info">Seguimiento</a>
                        <form method="post" action="<?= url('/units/' . $u->id . '/publish') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-<?= $u->status === 'PUBLISHED' ? 'warning' : 'success' ?>">
                                <?= $u->status === 'PUBLISHED' ? 'Despublicar' : 'Publicar' ?>
                            </button>
                        </form>
                        <a href="<?= url('/units/' . $u->id . '/edit-meta') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <form method="post" action="<?= url('/units/' . $u->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta unidad?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $units): ?><tr><td colspan="4" class="text-center text-muted py-4">Sin unidades. Cree la primera unidad.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
