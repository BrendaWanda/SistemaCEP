<?php
/** @var array|null $mantenimiento @var array $equipos @var array $usuarios
 *  @var array $tipos @var array $resultados @var string $fecha_hoy @var string $accion */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($mantenimiento[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Mantenimiento' : '➕ Nuevo Mantenimiento' ?>
    </div>
</div>

<div class="card" style="max-width:750px">
    <div class="card-header">
        <span class="card-title">Datos del mantenimiento</span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/m3/<?= $esEditar
                ? $mantenimiento['id'].'/editar' : 'nuevo' ?>">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Equipo <span class="form-required">*</span>
                </label>
                <select name="equipo_id" class="form-control" required>
                    <option value="">Seleccione el equipo...</option>
                    <?php foreach ($equipos as $e): ?>
                    <option value="<?= $e['id'] ?>"
                            <?= ($mantenimiento['equipo_id'] ?? '') == $e['id']
                                ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['codigo'].' — '.$e['nombre']) ?>
                        (<?= htmlspecialchars($e['linea_nombre']) ?>)
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Tipo <span class="form-required">*</span>
                </label>
                <select name="tipo" class="form-control" required id="tipoSelect">
                    <option value="">Seleccione...</option>
                    <?php foreach ($tipos as $val => $info): ?>
                    <option value="<?= $val ?>"
                            <?= $v('tipo') === $val ? 'selected' : '' ?>>
                        <?= $info['label'] ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">
                Descripción del trabajo <span class="form-required">*</span>
            </label>
            <textarea name="descripcion_trabajo" class="form-control"
                        rows="2" required
                        placeholder="Describe el trabajo realizado..."><?=
                $v('descripcion_trabajo')
            ?></textarea>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Fecha programada</label>
                <input type="date" name="fecha_programada"
                        class="form-control"
                        value="<?= $v('fecha_programada') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Resultado</label>
                <select name="resultado" class="form-control">
                    <?php foreach ($resultados as $val => $info): ?>
                    <option value="<?= $val ?>"
                            <?= $v('resultado','completado') === $val
                                ? 'selected' : '' ?>>
                        <?= $info['label'] ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Fecha y hora inicio</label>
                <input type="datetime-local" name="fecha_inicio"
                        class="form-control" id="fechaInicio"
                        value="<?= $v('fecha_inicio') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha y hora fin</label>
                <input type="datetime-local" name="fecha_fin"
                        class="form-control" id="fechaFin"
                        value="<?= $v('fecha_fin') ?>">
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Duración (minutos)</label>
                <input type="number" name="duracion_min"
                        class="form-control" id="duracionMin"
                        value="<?= $v('duracion_min') ?>" min="0"
                        placeholder="Se calcula automáticamente">
            </div>
            <div class="form-group">
                <label class="form-label">Costo estimado (Bs.)</label>
                <input type="number" name="costo_estimado"
                        class="form-control"
                        value="<?= $v('costo_estimado') ?>"
                        step="0.01" min="0">
            </div>
        </div>

        <!-- Sección correctivo -->
        <div id="seccionCorrectivo"
                style="<?= $v('tipo') === 'correctivo' ? '' : 'display:none' ?>">
            <div style="background:#fef2f2;border:1px solid #fca5a5;
                        border-radius:8px;padding:14px;margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:#dc2626;
                            margin-bottom:10px">
                    ⚠️ Datos de falla (mantenimiento correctivo)
                </div>
                <div class="form-group">
                    <label class="form-label">Falla detectada</label>
                    <textarea name="falla_detectada" class="form-control"
                                rows="2"><?= $v('falla_detectada') ?></textarea>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Causa raíz</label>
                        <textarea name="causa_raiz" class="form-control"
                                    rows="2"><?= $v('causa_raiz') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Acción correctiva</label>
                        <textarea name="accion_correctiva" class="form-control"
                                    rows="2"><?= $v('accion_correctiva') ?></textarea>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group" style="margin-bottom:0">
                        <label style="display:flex;align-items:center;
                                        gap:8px;cursor:pointer">
                            <input type="checkbox" name="paro_produccion"
                                    <?= $mantenimiento['paro_produccion'] ?? false
                                        ? 'checked' : '' ?>
                                    style="width:16px;height:16px">
                            <span style="font-weight:500">
                                ¿Causó paro de producción?
                            </span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Tiempo de paro (min)</label>
                        <input type="number" name="tiempo_paro_min"
                                class="form-control"
                                value="<?= $v('tiempo_paro_min') ?>" min="0">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Componentes cambiados</label>
                <textarea name="componentes_cambiados" class="form-control"
                            rows="2"
                            placeholder="Lista de repuestos/componentes..."><?=
                    $v('componentes_cambiados')
                ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control"
                            rows="2"><?= $v('observaciones') ?></textarea>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Ejecutado por</label>
                <select name="ejecutado_por_id" class="form-control">
                    <?php foreach ($usuarios as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                        <?= ($mantenimiento['ejecutado_por_id'] ?? \App\Core\Auth::id()) == $id
                            ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Supervisado por</label>
                <select name="supervisado_por_id" class="form-control">
                    <option value="">— Sin supervisor —</option>
                    <?php foreach ($usuarios as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                        <?= ($mantenimiento['supervisado_por_id'] ?? '') == $id
                            ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m3" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <?= $esEditar ? '💾 Guardar cambios' : '✅ Registrar mantenimiento' ?>
        </button>
    </div>
    </form>
</div>

<script>
// Mostrar sección correctivo según tipo
document.getElementById('tipoSelect').addEventListener('change', function() {
    document.getElementById('seccionCorrectivo').style.display =
        this.value === 'correctivo' ? '' : 'none';
});

// Calcular duración automática
function calcularDuracion() {
    const ini = document.getElementById('fechaInicio').value;
    const fin = document.getElementById('fechaFin').value;
    if (!ini || !fin) return;
    const diff = (new Date(fin) - new Date(ini)) / 60000;
    if (diff > 0) document.getElementById('duracionMin').value = Math.round(diff);
}
document.getElementById('fechaInicio').addEventListener('change', calcularDuracion);
document.getElementById('fechaFin').addEventListener('change', calcularDuracion);
</script>