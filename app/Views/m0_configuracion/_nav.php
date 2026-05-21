<?php
$navItems = [
    ['url'=>'/m0/lineas',    'label'=>'Líneas',    'icon'=>'bi-diagram-2-fill', 'prefijo'=>'/m0/lineas'],
    ['url'=>'/m0/productos', 'label'=>'Productos', 'icon'=>'bi-box-seam-fill',  'prefijo'=>'/m0/productos'],
    ['url'=>'/m0/equipos',   'label'=>'Equipos',   'icon'=>'bi-cpu-fill',       'prefijo'=>'/m0/equipos'],
    ['url'=>'/m0/insumos',   'label'=>'Insumos',   'icon'=>'bi-basket-fill',    'prefijo'=>'/m0/insumos'],
    ['url'=>'/m0/recetas',   'label'=>'Recetas',   'icon'=>'bi-journal-text',   'prefijo'=>'/m0/recetas'],
];
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri      = ($basePath && str_starts_with($uriRaw, $basePath))
            ? substr($uriRaw, strlen($basePath)) : $uriRaw;
?>
<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;
            background:#ffffff;border:1px solid #e2e8f0;
            border-radius:10px;padding:6px">
    <?php foreach ($navItems as $item):
        $isActive = str_starts_with($uri, $item['prefijo']);
    ?>
    <a href="<?= APP_URL . $item['url'] ?>"
        style="display:inline-flex;align-items:center;gap:8px;
            padding:8px 18px;border-radius:7px;
            font-size:13.5px;font-weight:<?= $isActive ? '600' : '500' ?>;
            text-decoration:none;transition:background .12s,color .12s;
            background:<?= $isActive ? '#1a2035' : 'transparent' ?>;
            color:<?= $isActive ? '#ffffff' : '#64748b' ?>">
        <i class="bi <?= $item['icon'] ?>"
            style="font-size:14px"></i>
        <?= $item['label'] ?>
    </a>
    <?php endforeach ?>
</div>