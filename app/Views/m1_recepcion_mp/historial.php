<?php
/** @var array $resultado @var array $filtros @var array $insumos
 *  @var array $proveedores @var array $decisiones @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div class="page-title">📋 Historial de Recepciones</div>
    <div class="page-sub">
        <?= $resultado['total'] ?> registros encontrados
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"><span class="card-title">🔍 Filtros</span></div>
    <form method="GET" action="<?= APP_URL ?>/m1/historial">
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Insumo</label>
                <select name="insumo_id" class="form-control">
                    <option value="">Todos los insumos</option>
                    <?php foreach ($insumos as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                            <?= $filtros['insumo_id'] == $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Proveedor</label>
                <select name="proveedor_id" class="form-control">
                    <option value="">Todos los proveedores</option>
                    <?php foreach ($proveedores as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                            <?= $filtros['proveedor_id'] == $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Decisión</label>
                <select name="decision" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($decisiones as $val => $info): ?>
                    <option value="<?= $val ?>"
                            <?= $filtros['decision'] === $val ? 'selected' : '' ?>>
                        <?= $info['label'] ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Fecha desde</label>
                <input type="date" name="fecha_desde" class="form-control"
                        value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha hasta</label>
                <input type="date" name="fecha_hasta" class="form-control"
                        value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m1/historial" class="btn btn-secondary">Limpiar</a>
        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
    </div>
    </form>
</div>

<!-- Tabla de resultados -->
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Insumo</th>
                        <th>Proveedor</th>
                        <th>Lote</th>
                        <th style="text-align:center">Cantidad</th>
                        <th style="text-align:center">Vencimiento</th>
                        <th style="text-align:center">Fecha análisis</th>
                        <th style="text-align:center">Decisión</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($resultado['data'])): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding:40px">
                        No se encontraron recepciones con los filtros aplicados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($resultado['data'] as $r):
                    $dec = $decisiones[$r['decision']] ?? ['label'=>$r['decision'],'badge'=>'badge-muted'];
                    $diasVence = (int)((strtotime($r['fecha_vencimiento']) - time()) / 86400);
                ?>
                <tr>
                    <td>
                        <code style="background:#f1f5f9;padding:2px 6px;
                                    border-radius:4px;font-size:11px">
                            <?= htmlspecialchars($r['codigo_recepcion']) ?>
                        </code>
                    </td>
                    <td>
                        <strong style="font-size:12px">
                            <?= htmlspecialchars($r['insumo_nombre']) ?>
                        </strong>
                        <div style="font-size:10px;color:#94a3b8">
                            <?= htmlspecialchars($r['insumo_codigo']) ?>
                        </div>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($r['proveedor_nombre']) ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($r['lote_proveedor']) ?>
                    </td>
                    <td style="text-align:center;font-size:12px;font-weight:600">
                        <?= number_format($r['cantidad_recibida'],2) ?>
                        <?= htmlspecialchars($r['unidad_medida']) ?>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <span style="color:<?= $diasVence < 0 ? '#dc2626' : ($diasVence < 30 ? '#d97706' : '#374151') ?>;font-weight:<?= $diasVence < 30 ? '600' : '400' ?>">
                            <?= date('d/m/Y', strtotime($r['fecha_vencimiento'])) ?>
                        </span>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?= date('d/m/Y', strtotime($r['fecha_analisis'])) ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $dec['badge'] ?>">
                            <?= $dec['label'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/m1/<?= $r['id'] ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                        <a href="<?= APP_URL ?>/m1/<?= $r['id'] ?>/imprimir"
                            class="btn btn-sm btn-secondary" target="_blank">🖨️</a>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($resultado['total_paginas'] > 1): ?>
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9;
                display:flex;justify-content:space-between;align-items:center">
        <div style="font-size:12px;color:#64748b">
            Página <?= $resultado['pagina'] ?> de <?= $resultado['total_paginas'] ?>
            · <?= $resultado['total'] ?> registros
        </div>
        <div class="pagination">
            <?php if ($resultado['tiene_anterior']): ?>
            <a href="?<?= http_build_query(array_merge($filtros, ['pagina'=>$resultado['pagina']-1])) ?>"
                class="page-link">‹ Anterior</a>
            <?php endif ?>
            <?php if ($resultado['tiene_siguiente']): ?>
            <a href="?<?= http_build_query(array_merge($filtros, ['pagina'=>$resultado['pagina']+1])) ?>"
                class="page-link">Siguiente ›</a>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>
</div>