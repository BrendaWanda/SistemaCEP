<?php
$navItems = [
    ['url'=>'/m0/lineas',    'label'=>'Líneas',    'icon'=>'🏗️', 'prefijo'=>'/m0/lineas'],
    ['url'=>'/m0/productos', 'label'=>'Productos',  'icon'=>'🍞', 'prefijo'=>'/m0/productos'],
    ['url'=>'/m0/equipos',   'label'=>'Equipos',    'icon'=>'⚙️', 'prefijo'=>'/m0/equipos'],
    ['url'=>'/m0/insumos',   'label'=>'Insumos',    'icon'=>'📦', 'prefijo'=>'/m0/insumos'],
    ['url'=>'/m0/recetas',   'label'=>'Recetas',    'icon'=>'📋', 'prefijo'=>'/m0/recetas'],
];
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri      = ($basePath && str_starts_with($uriRaw, $basePath))
            ? substr($uriRaw, strlen($basePath)) : $uriRaw;
?>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px">
    <?php foreach ($navItems as $item): ?>
    <?php $isActive = str_starts_with($uri, $item['prefijo']); ?>
    <a href="<?= APP_URL . $item['url'] ?>"
        style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
                border-radius:8px;font-size:13px;font-weight:<?= $isActive ? '700' : '500' ?>;
                text-decoration:none;border:1px solid <?= $isActive ? '#468189' : '#e2e8f0' ?>;
                background:<?= $isActive ? '#031926' : '#fff' ?>;
                color:<?= $isActive ? '#fff' : '#77aca2' ?>">
        <?= $item['icon'] ?> <?= $item['label'] ?>
    </a>
    <?php endforeach ?>
</div>