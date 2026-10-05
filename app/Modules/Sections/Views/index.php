<?php /** @var array $sections */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Secciones</h1>
    <a href="<?= url('/sections/create') ?>" class="btn btn-primary btn-sm">+ Nueva sección</a>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Sección</th><th>Período</th><th>Trayecto</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($sections as $s): ?>
                <tr>
                    <td><a href="<?= url('/sections/' . $s->id) ?>"><?= e($s->label()) ?></a></td>
                    <td><?= e($s->period_name) ?></td>
                    <td><?= e($s->track_name) ?></td>
                    <td><span class="badge <?= $s->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>"><?= e($s->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/sections/' . $s->id) ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                        <a href="<?= url('/sections/' . $s->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <form method="post" action="<?= url('/sections/' . $s->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta sección?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $sections): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay secciones registradas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
