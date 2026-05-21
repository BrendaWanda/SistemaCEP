<?php
/** @var array|null $equipo @var array $lineas @var array $tipos @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($equipo[$campo] ?? $def); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <?= $esEditar ? 'Editar Equipo' : 'Registrar Equipo' ?>
        </div>
        <div class="page-sub">
            <?= $esEditar
                ? 'Modifique los datos del equipo'
                : 'Complete los datos para registrar un nuevo equipo' ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/equipos" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:720px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-cpu-fill" style="color:#4f8ef7;margin-right:6px"></i>
            Datos del equipo
        </span>
    </div>
    <form method="POST"
        action="<?= APP_URL ?>/m0/equipos/<?= $esEditar
            ? $equipo['id'].'/editar' : 'nuevo' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <!-- Línea y tipo -->
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

            <!-- Código y nombre -->
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Código <span class="form-required">*</span>
                    </label>
                    <input type="text" name="codigo" class="form-control"
                        value="<?= $v('codigo') ?>"
                        placeholder="Ej: EQ-BAL-01"
                        style="text-transform:uppercase;font-family:monospace"
                        required>
                    <div class="form-hint">
                        <i class="bi bi-info-circle"></i>
                        Ej: EQ-HOR-01, EQ-BAL-02, EQ-TER-01
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Nombre <span class="form-required">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control"
                        value="<?= $v('nombre') ?>"
                        placeholder="Ej: Balanza de proceso N°1"
                        required>
                </div>
            </div>

            <!-- Marca, modelo, serie -->
            <div class="form-row cols-3">
                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-control"
                        value="<?= $v('marca') ?>"
                        placeholder="Ej: OHAUS">
                </div>
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" class="form-control"
                        value="<?= $v('modelo') ?>"
                        placeholder="Ej: Scout SKX52">
                </div>
                <div class="form-group">
                    <label class="form-label">N° de serie</label>
                    <input type="text" name="serie" class="form-control"
                        value="<?= $v('serie') ?>"
                        placeholder="Número de serie">
                </div>
            </div>

            <!-- Calibración -->
            <div style="border:1px solid #e2e8f0;border-radius:8px;
                        padding:16px;margin-bottom:16px;background:#fafbfc">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                    <input type="checkbox" name="requiere_calibracion"
                        id="reqCalibr"
                        <?= ($equipo['requiere_calibracion'] ?? false) ? 'checked' : '' ?>
                        style="width:16px;height:16px;accent-color:#4f8ef7;cursor:pointer">
                    <label for="reqCalibr"
                        style="font-weight:600;cursor:pointer;
                                display:flex;align-items:center;gap:7px;
                                color:#0f172a;font-size:14px">
                        <i class="bi bi-search" style="color:#4f8ef7"></i>
                        Este equipo requiere calibración periódica
                        <span style="font-size:11px;font-weight:400;color:#64748b">
                            (verificable por SIREMU)
                        </span>
                    </label>
                </div>
                <div id="calibrSection"
                    style="<?= ($equipo['requiere_calibracion'] ?? false)
                                ? '' : 'display:none' ?>">
                    <div class="form-row cols-2">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">
                                <i class="bi bi-calendar-check" style="margin-right:4px"></i>
                                Frecuencia de calibración (días)
                            </label>
                            <input type="number" name="frecuencia_calibr_dias"
                                class="form-control"
                                value="<?= $v('frecuencia_calibr_dias') ?>"
                                min="1" placeholder="Ej: 365">
                            <div class="form-hint">365 = anual · 180 = semestral</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">
                                <i class="bi bi-wrench" style="margin-right:4px"></i>
                                Frecuencia de mantenimiento (días)
                            </label>
                            <input type="number" name="frecuencia_mant_dias"
                                class="form-control"
                                value="<?= $v('frecuencia_mant_dias') ?>"
                                min="1" placeholder="Ej: 90">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"
                        placeholder="Notas adicionales sobre el equipo..."><?= $v('observaciones') ?></textarea>
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m0/equipos" class="btn btn-secondary">
                <i class="bi bi-x-lg"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <?php if ($esEditar): ?>
                    <i class="bi bi-floppy"></i> Guardar cambios
                <?php else: ?>
                    <i class="bi bi-check-lg"></i> Registrar equipo
                <?php endif ?>
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