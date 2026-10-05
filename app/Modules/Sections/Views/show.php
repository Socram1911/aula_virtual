<?php
/** @var \App\Modules\Sections\Models\Section $section @var array $students @var array $available */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1"><?= e($section->label()) ?></h1>
        <span class="text-muted"><?= e($section->period_name) ?> · <?= e($section->track_name) ?></span>
    </div>
    <a href="<?= url('/sections') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Estudiantes inscritos</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Estudiante</th><th>Correo</th><th>Inscripción</th><th class="text-end">Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><?= e($st['last_name'] . ', ' . $st['first_name']) ?></td>
                                <td><?= e($st['email']) ?></td>
                                <td><?= e($st['enrollment_date']) ?></td>
                                <td class="text-end">
                                    <?php if ($st['enrollment_status'] === 'ENROLLED'): ?>
                                        <form method="post" action="<?= url('/sections/' . $section->id . '/withdraw') ?>" class="d-inline" onsubmit="return confirm('¿Retirar este estudiante?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="student_id" value="<?= (int) $st['student_id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger">Retirar</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Retirado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (! $students): ?><tr><td colspan="4" class="text-center text-muted py-3">Sin estudiantes inscritos.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Inscribir estudiante</h2>
                <form method="post" action="<?= url('/sections/' . $section->id . '/enroll') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <select name="student_id" class="form-select" required>
                            <option value="">Seleccione un estudiante…</option>
                            <?php foreach ($available as $a): ?>
                                <option value="<?= (int) $a['id'] ?>"><?= e($a['last_name'] . ', ' . $a['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de inscripción</label>
                        <input type="date" name="enrollment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <button class="btn btn-primary w-100" <?= $available ? '' : 'disabled' ?>>Inscribir</button>
                </form>
            </div>
        </div>
    </div>
</div>
