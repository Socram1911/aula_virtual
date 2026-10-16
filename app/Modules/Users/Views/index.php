<?php /** @var array $users @var array $pending @var string $title */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e($title) ?></h1>
    <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-sm">+ Nuevo usuario</a>
</div>

<?php if ($pending): ?>
<div class="card card-av mb-3">
    <div class="card-body">
        <h2 class="h6 mb-2">Solicitudes de registro pendientes</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Nombre</th><th>Correo</th><th class="text-end">Acción</th></tr></thead>
                <tbody>
                <?php foreach ($pending as $p): ?>
                    <tr>
                        <td><?= e($p->last_name . ', ' . $p->first_name) ?></td>
                        <td><?= e($p->email) ?></td>
                        <td class="text-end">
                            <a href="<?= url('/users/' . $p->id . '/approve') ?>" class="btn btn-sm btn-success">Aprobar y asignar rol</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Nombre</th><th>Correo</th><th>Roles</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u->last_name . ', ' . $u->first_name) ?></td>
                    <td><?= e($u->email) ?></td>
                    <td>
                        <?php foreach ($u->roles as $r): ?>
                            <span class="badge bg-info text-dark"><?= e($r) ?></span>
                        <?php endforeach; ?>
                        <?php if (! $u->roles): ?><span class="text-muted small">Sin rol</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u->status === 'ACTIVE'): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php elseif ($u->status === 'PENDING'): ?>
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($u->isPending()): ?>
                            <a href="<?= url('/users/' . $u->id . '/approve') ?>" class="btn btn-sm btn-success">Aprobar</a>
                        <?php else: ?>
                            <a href="<?= url('/users/' . $u->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('/users/' . $u->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $users): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
