<?php
/** @var array|null $equipo @var array $lineas @var array $tipos @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($equipo[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Equipo' : '➕ Registrar Equipo' ?>
    </div>
</div>

<div class="card" style="max-width:700px">
    <div class="card-header"><span class="card-title">Datos del equipo</span></div>
    <form method="POST"
          action="<?= APP_URL ?>/m0/equipos/<?= $esEditar ? $equipo['id'].'/editar' : 'nuevo' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Línea de producción <span class="form-required">*</span>
                    </label>
                    <select name="linea_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($lineas as $id => $nombre): ?>
                        <option value="<?= $id ?>"
                                <?= ($equipo['linea_id'] ?? '') == $id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Tipo de equipo <span class="form-required">*</span>
                    </label>
                    <select name="tipo" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($tipos as $key => $label): ?>
                        <option value="<?= $key ?>"
                                <?= $v('tipo') === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Código <span class="form-required">*</span>
                    </label>
                    <input type="text" name="codigo" class="form-control"
                           value="<?= $v('codigo') ?>"
                           placeholder="Ej: EQ-BAL-01"
                           style="text-transform:uppercase" required>
                    <div class="form-hint">Ej: EQ-HOR-01, EQ-BAL-02, EQ-TER-01</div>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Nombre <span class="form-required">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control"
                           value="<?= $v('nombre') ?>"
                           placeholder="Ej: Balanza de proceso N°1" required>
                </div>
            </div>

            <div class="form-row cols-3">
                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-control"
                           value="<?= $v('marca') ?>" placeholder="Ej: OHAUS">
                </div>
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" class="form-control"
                           value="<?= $v('modelo') ?>" placeholder="Ej: Scout SKX52">
                </div>
                <div class="form-group">
                    <label class="form-label">N° de serie</label>
                    <input type="text" name="serie" class="form-control"
                           value="<?= $v('serie') ?>">
                </div>
            </div>

            <!-- Calibración -->
            <div style="border:1px solid #e2e8f0;border-radius:8px;
                        padding:14px;margin-bottom:16px">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <input type="checkbox" name="requiere_calibracion" id="reqCalibr"
                           <?= ($equipo['requiere_calibracion'] ?? false) ? 'checked' : '' ?>
                           style="width:16px;height:16px">
                    <label for="reqCalibr" style="font-weight:600;cursor:pointer">
                        🔬 Este equipo requiere calibración periódica (verificable por SIREMU)
                    </label>
                </div>
                <div id="calibrSection"
                     style="<?= ($equipo['requiere_calibracion'] ?? false) ? '' : 'display:none' ?>">
                    <div class="form-row cols-2">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Frecuencia de calibración (días)</label>
                            <input type="number" name="frecuencia_calibr_dias"
                                   class="form-control"
                                   value="<?= $v('frecuencia_calibr_dias') ?>"
                                   min="1" placeholder="Ej: 365">
                            <div class="form-hint">365 = anual · 180 = semestral</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Frecuencia de mantenimiento (días)</label>
                            <input type="number" name="frecuencia_mant_dias"
                                   class="form-control"
                                   value="<?= $v('frecuencia_mant_dias') ?>"
                                   min="1" placeholder="Ej: 90">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"
                          placeholder="Notas adicionales sobre el equipo...">
<?= $v('observaciones') ?></textarea>
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m0/equipos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $esEditar ? '💾 Guardar cambios' : '✅ Registrar equipo' ?>
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('reqCalibr').addEventListener('change', function () {
    document.getElementById('calibrSection').style.display =
        this.checked ? '' : 'none';
});
</script>