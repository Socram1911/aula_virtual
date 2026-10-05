<?php

declare(strict_types=1);

namespace App\Modules\Units\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Session\Session;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Files\Services\FileStorageService;
use App\Modules\Sabers\Models\Unit;
use App\Modules\Sabers\Services\AssignmentService;
use App\Modules\Sabers\Services\SaberService;
use App\Modules\Units\Services\UnitEditorService;

final class UnitEditorController extends Controller
{
    private UnitEditorService $editor;
    private SaberService $sabers;
    private FileStorageService $files;
    private AssignmentService $assignments;

    public function __construct()
    {
        parent::__construct();
        $this->editor = new UnitEditorService();
        $this->sabers = new SaberService();
        $this->files = new FileStorageService();
        $this->assignments = new AssignmentService();
    }

    private function unit(int $id): Unit
    {
        return $this->sabers->findUnit($id);
    }

    /** Un docente solo puede editar unidades de Saberes que le fueron asignados. */
    private function guardUnit(int $unitId): void
    {
        if (Auth::hasRole('ADMIN')) {
            return;
        }
        $unit = $this->sabers->findUnit($unitId);
        if (! $this->assignments->teacherHasSaber((int) Auth::id(), $unit->saber_id)) {
            throw HttpException::forbidden('No tiene asignado el Saber de esta unidad.');
        }
    }

    private function guardBlock(int $blockId): void
    {
        if (Auth::hasRole('ADMIN')) {
            return;
        }
        $block = $this->editor->findBlock($blockId);
        $this->guardUnit($block->unit_id);
    }

    public function edit(int $unitId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardUnit($unitId);
        $unit = $this->unit($unitId);
        $this->render('Units/editor', [
            'title' => 'Editor: ' . $unit->title,
            'unit' => $unit,
            'blocks' => $this->editor->blocks($unitId),
            'types' => ContentBlock::TYPES,
        ]);
    }

    public function store(int $unitId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardUnit($unitId);
        $this->unit($unitId);
        $input = $this->request->all();

        // Si se sube un archivo (IMAGE/PDF), se registra y se usa su id como contenido.
        $type = strtoupper((string) ($input['type'] ?? 'TEXT'));
        if (in_array($type, ['IMAGE', 'PDF'], true)) {
            $file = $this->request->file('file');
            if ($file === null) {
                $this->backWithErrors(url('/units/' . $unitId . '/edit'), ['Debe seleccionar un archivo para este tipo de bloque.']);
                return;
            }
            $stored = $this->files->store($file, (int) Auth::id());
            $input['content'] = (string) $stored->id;
        }

        $this->handle(function () use ($unitId, $input): void {
            $this->editor->addBlock($unitId, $input);
            Session::flash('_success', 'Bloque agregado.');
        }, url('/units/' . $unitId . '/edit'));
    }

    public function update(int $blockId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardBlock($blockId);
        $block = $this->editor->findBlock($blockId);
        $this->handle(function () use ($blockId): void {
            $this->editor->updateBlock($blockId, $this->request->all());
            Session::flash('_success', 'Bloque actualizado.');
        }, url('/units/' . $block->unit_id . '/edit'));
    }

    public function duplicate(int $blockId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardBlock($blockId);
        $block = $this->editor->duplicateBlock($blockId);
        Session::flash('_success', 'Bloque duplicado.');
        $this->redirect(url('/units/' . $block->unit_id . '/edit'));
    }

    public function move(int $blockId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardBlock($blockId);
        $block = $this->editor->findBlock($blockId);
        $direction = (string) $this->request->input('direction', 'up');
        $this->editor->moveBlock($blockId, $direction);
        $this->redirect(url('/units/' . $block->unit_id . '/edit'));
    }

    public function toggle(int $blockId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardBlock($blockId);
        $block = $this->editor->findBlock($blockId);
        $this->editor->setBlockStatus($blockId, $block->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE');
        $this->redirect(url('/units/' . $block->unit_id . '/edit'));
    }

    public function destroy(int $blockId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardBlock($blockId);
        $block = $this->editor->findBlock($blockId);
        $this->editor->deleteBlock($blockId);
        Session::flash('_success', 'Bloque eliminado.');
        $this->redirect(url('/units/' . $block->unit_id . '/edit'));
    }
}
