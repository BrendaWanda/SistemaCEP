<?php
/** @var array $menus @var array $trimestres @var array $niveles @var bool $canWrite */
?>

<div class="page-header">
    <div>
        <div class="page-title">📅 Menú Trimestral</div>
        <div class="page-sub">
            Registro de menús por trimestre · Contrato DBC-GAMLP Lote N°2
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/menu-trimestral/nuevo" class="btn btn-primary">
            + Nuevo menú
        </a>
    </div>
    <?php endif ?>
</div>

<?php if (empty($menus)): ?>
<div class="card">
    <div class="card-body text-center" style="padding:60px;color:#94a3b8">
        <div style="font-size:48px;margin-bottom:12px">📅</div>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px">
            No hay menús trimestrales registrados
        </div>
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/menu-trimestral/nuevo" class="btn btn-primary">
            Registrar primer menú
        </a>
        <?php endif ?>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Menú</th>
                        <th style="text-align:center">Trimestre</th>
                        <th style="text-align:center">Año</th>
                        <th style="text-align:center">Período</th>
                        <th>Nivel</th>
                        <th>Recibido de</th>
                        <th style="text-align:center">Lotes prod.</th>
                        <th style="text-align:center">Und. prod.</th>
                        <th style="text-align:center">Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($menus as $m): ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/menu-trimestral/<?= $m['id'] ?>"
                            style="font-weight:700;color:#1d4ed8;text-decoration:none">
                            <?= htmlspecialchars($m['nombre']) ?>
                        </a>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-info">
                            T<?= $m['trimestre'] ?>
                        </span>
                    </td>
                    <td style="text-align:center;font-weight:600">
                        <?= $m['anio'] ?>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?= date('d/m/Y', strtotime($m['fecha_inicio'])) ?>
                        <span style="color:#94a3b8"> al </span>
                        <?= date('d/m/Y', strtotime($m['fecha_fin'])) ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($m['nivel'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($m['recibido_de'] ?? '—') ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-muted">
                            <?= (int)$m['total_lotes'] ?>
                        </span>
                    </td>
                    <td style="text-align:center;font-weight:600;color:#15803d">
                        <?= number_format((int)$m['und_producidas']) ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $m['activo'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $m['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/menu-trimestral/<?= $m['id'] ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                        <?php if ($canWrite): ?>
                        <a href="<?= APP_URL ?>/menu-trimestral/<?= $m['id'] ?>/editar"
                            class="btn btn-sm btn-secondary">Editar</a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif ?>