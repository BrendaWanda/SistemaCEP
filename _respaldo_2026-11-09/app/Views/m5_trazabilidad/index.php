<?php
/** @var string $termino @var array $resultados @var array $ultimosLotes */
?>

<div class="page-header">
    <div>
        <div class="page-title">🔗 Trazabilidad</div>
        <div class="page-sub">
            Consulta el árbol completo de un lote — desde la MP hasta la liberación PT.
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">🔍 Buscar lote</span>
    </div>
    <form method="GET" action="<?= APP_URL ?>/m5">
    <div class="card-body">
        <div style="display:flex;gap:10px;align-items:flex-end">
            <div class="form-group" style="flex:1;margin-bottom:0">
                <label class="form-label">
                    Código de lote, nombre o código de producto
                </label>
                <input type="text" name="q" class="form-control"
                        value="<?= htmlspecialchars($termino ?? '') ?>"
                        placeholder="Ej: 18-05, Pan Sarnita, PT-PAN-SARN-INT"
                        autofocus style="font-size:15px">
            </div>
            <button type="submit" class="btn btn-primary"
                    style="padding:10px 24px">
                🔍 Buscar
            </button>
        </div>
    </div>
    </form>

    <!-- Resultados de búsqueda -->
    <?php if (!empty($termino)): ?>
    <div style="border-top:1px solid #f1f5f9;padding:14px 20px">
        <?php if (empty($resultados)): ?>
        <div class="text-center text-muted" style="padding:20px">
            No se encontraron lotes para
            "<strong><?= htmlspecialchars($termino) ?></strong>"
        </div>
        <?php else: ?>
        <div style="font-size:12px;font-weight:700;color:#64748b;
                    text-transform:uppercase;margin-bottom:8px">
            <?= count($resultados) ?> resultado(s) para
            "<?= htmlspecialchars($termino) ?>"
        </div>
        <div style="display:flex;flex-direction:column;gap:6px">
        <?php foreach ($resultados as $r):
            $estColor = match($r['estado']) {
                'liberado'   => '#15803d',
                'cuarentena' => '#d97706',
                'rechazado'  => '#dc2626',
                default      => '#64748b',
            };
        ?>
        <a href="<?= APP_URL ?>/m5/lote/<?= urlencode($r['codigo_lote']) ?>"
            style="display:flex;align-items:center;gap:12px;padding:10px 14px;
                    border:1px solid #e2e8f0;border-radius:8px;
                    text-decoration:none;background:#fff;
                    transition:background .15s"
            onmouseover="this.style.background='#f8fafc'"
            onmouseout="this.style.background='#fff'">
            <span style="font-family:monospace;font-size:16px;
                            font-weight:800;color:#1d4ed8;min-width:80px">
                <?= htmlspecialchars($r['codigo_lote']) ?>
            </span>
            <span style="font-size:13px;color:#374151;flex:1">
                <?= htmlspecialchars($r['producto_nombre']) ?>
                <span style="font-size:11px;color:#94a3b8;margin-left:6px">
                    <?= htmlspecialchars($r['producto_codigo']) ?>
                </span>
            </span>
            <span style="font-size:12px;color:#64748b">
                <?= date('d/m/Y', strtotime($r['fecha_produccion'])) ?>
            </span>
            <span style="font-size:11px;font-weight:600;color:<?= $estColor ?>">
                <?= ucfirst($r['estado']) ?>
            </span>
            <span style="color:#94a3b8">→</span>
        </a>
        <?php endforeach ?>
        </div>
        <?php endif ?>
    </div>
    <?php endif ?>
</div>

<!-- Acceso rápido — últimos lotes -->
<div class="card">
    <div class="card-header">
        <span class="card-title">🕐 Últimos lotes producidos</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código lote</th>
                    <th>Producto</th>
                    <th style="text-align:center">Fecha producción</th>
                    <th style="text-align:center">Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ultimosLotes)): ?>
            <tr>
                <td colspan="5" class="text-center text-muted"
                    style="padding:30px">
                    No hay lotes registrados aún.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($ultimosLotes as $l):
                $estColor = match($l['estado']) {
                    'liberado'   => ['badge-success', '✅ Liberado'],
                    'cuarentena' => ['badge-warning', '⚠️ Cuarentena'],
                    'rechazado'  => ['badge-danger',  '❌ Rechazado'],
                    'en_proceso' => ['badge-info',    '⚙️ En proceso'],
                    default      => ['badge-muted',   ucfirst($l['estado'])],
                };
            ?>
            <tr>
                <td>
                    <span style="font-family:monospace;font-size:15px;
                                    font-weight:800;color:#1d4ed8">
                        <?= htmlspecialchars($l['codigo_lote']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($l['producto_nombre']) ?></td>
                <td style="text-align:center;font-size:12px">
                    <?= date('d/m/Y', strtotime($l['fecha_produccion'])) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $estColor[0] ?>">
                        <?= $estColor[1] ?>
                    </span>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/m5/lote/<?= urlencode($l['codigo_lote']) ?>"
                        class="btn btn-sm btn-secondary">
                        🔗 Ver trazabilidad
                    </a>
                </td>
            </tr>
            <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>