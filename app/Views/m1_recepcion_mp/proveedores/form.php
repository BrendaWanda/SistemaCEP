<?php
/** @var array|null $proveedor @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($proveedor[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Proveedor' : '➕ Nuevo Proveedor' ?>
    </div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header">
        <span class="card-title">Datos del proveedor</span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/m1/proveedores/<?= $esEditar
                ? $proveedor['id'].'/editar' : 'nuevo' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <div class="form-group">
                <label class="form-label">
                    Nombre <span class="form-required">*</span>
                </label>
                <input type="text" name="nombre" class="form-control"
                        value="<?= $v('nombre') ?>"
                        placeholder="Ej: SETAR S.R.L. / Letizia" required>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Procedencia</label>
                    <input type="text" name="procedencia" class="form-control"
                            value="<?= $v('procedencia') ?>"
                            placeholder="Ej: Santa Cruz - Bolivia">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control"
                            value="<?= $v('telefono') ?>"
                            placeholder="Ej: 591-3-3456789">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control"
                            value="<?= $v('email') ?>"
                            placeholder="contacto@proveedor.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Registro sanitario</label>
                    <input type="text" name="registro_sanitario" class="form-control"
                            value="<?= $v('registro_sanitario') ?>"
                            placeholder="Nro. registro sanitario">
                </div>
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m1/proveedores" class="btn btn-secondary">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <?= $esEditar ? '💾 Guardar cambios' : '✅ Crear proveedor' ?>
            </button>
        </div>
    </form>
</div>