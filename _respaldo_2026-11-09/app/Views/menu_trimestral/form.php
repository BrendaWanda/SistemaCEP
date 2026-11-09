<?php
/** @var array|null $menu @var array $trimestres @var array $niveles
 *  @var string $fecha_hoy @var int $anio_actual @var string $accion */
?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($menu[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Menú Trimestral' : '➕ Nuevo Menú Trimestral' ?>
    </div>
</div>

<div class="card" style="max-width:650px">
    <div class="card-header">
        <span class="card-title">Datos del menú</span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/menu-trimestral/<?= $esEditar
                ? $menu['id'].'/editar' : 'nuevo' ?>">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">

        <div class="form-group">
            <label class="form-label">
                Nombre del menú <span class="form-required">*</span>
            </label>
            <input type="text" name="nombre" class="form-control"
                    value="<?= $v('nombre') ?>"
                    placeholder="Ej: Menú T1 2026 — Secundaria"
                    required>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Trimestre <span class="form-required">*</span>
                </label>
                <select name="trimestre" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($trimestres as $num => $label): ?>
                    <option value="<?= $num ?>"
                            <?= (int)$v('trimestre') === $num ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Año</label>
                <input type="number" name="anio" class="form-control"
                        value="<?= $v('anio', $anio_actual) ?>"
                        min="2024" max="2030">
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Fecha inicio <span class="form-required">*</span>
                </label>
                <input type="date" name="fecha_inicio" class="form-control"
                        value="<?= $v('fecha_inicio') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Fecha fin <span class="form-required">*</span>
                </label>
                <input type="date" name="fecha_fin" class="form-control"
                        value="<?= $v('fecha_fin') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Nivel</label>
            <select name="nivel" class="form-control">
                <?php foreach ($niveles as $nivel): ?>
                <option value="<?= $nivel ?>"
                        <?= $v('nivel') === $nivel ? 'selected' : '' ?>>
                    <?= $nivel ?>
                </option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Recibido de</label>
                <input type="text" name="recibido_de" class="form-control"
                        value="<?= $v('recibido_de') ?>"
                        placeholder="Ej: Nutrición GAMLP">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha de recepción</label>
                <input type="date" name="fecha_recepcion" class="form-control"
                        value="<?= $v('fecha_recepcion') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Observaciones</label>
            <textarea name="obs" class="form-control" rows="3"><?=
                $v('obs')
            ?></textarea>
        </div>

        <?php if ($esEditar): ?>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="activo"
                        <?= ($menu['activo'] ?? 1) ? 'checked' : '' ?>
                        style="width:16px;height:16px">
                <span style="font-weight:500">Menú activo</span>
            </label>
        </div>
        <?php endif ?>

    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/menu-trimestral" class="btn btn-secondary">
            Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <?= $esEditar ? '💾 Guardar cambios' : '✅ Registrar menú' ?>
        </button>
    </div>
    </form>
</div>