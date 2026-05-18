<?php
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri      = ($basePath && str_starts_with($uriRaw, $basePath))
            ? substr($uriRaw, strlen($basePath)) : $uriRaw;

$navItems = [
    ['url'=>'/m1',                    'label'=>'Inicio',      'icon'=>'🏠', 'prefijo'=>'/m1'],
    ['url'=>'/m1/nueva',              'label'=>'Nueva recep.','icon'=>'➕', 'prefijo'=>'/m1/nueva'],
    ['url'=>'/m1/historial',          'label'=>'Historial',   'icon'=>'📋', 'prefijo'=>'/m1/historial'],
    ['url'=>'/m1/stock',              'label'=>'Stock MP',    'icon'=>'📦', 'prefijo'=>'/m1/stock'],
    ['url'=>'/m1/alertas-vencimiento','label'=>'Alertas',     'icon'=>'⚠️', 'prefijo'=>'/m1/alertas'],
    ['url'=>'/m1/proveedores',        'label'=>'Proveedores', 'icon'=>'🚛', 'prefijo'=>'/m1/proveedores'],
];
?>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px">
    <?php foreach ($navItems as $item):
        $active = str_starts_with($uri, $item['prefijo'])
                    && !($item['prefijo']==='/m1' && strlen($uri) > 3
                        && !str_starts_with($uri,'/m1/nueva')===false);
        // Inicio solo activo en exactamente /m1
        if ($item['prefijo'] === '/m1') {
            $active = $uri === '/m1' || $uri === '/m1/';
        }
    ?>
    <a href="<?= APP_URL . $item['url'] ?>"
        style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
                border-radius:8px;font-size:13px;
                font-weight:<?= $active ? '700' : '500' ?>;
                text-decoration:none;
                border:1px solid <?= $active ? '#0d9488' : '#e2e8f0' ?>;
                background:<?= $active ? '#0d9488' : '#fff' ?>;
                color:<?= $active ? '#fff' : '#475569' ?>">
        <?= $item['icon'] ?> <?= $item['label'] ?>
    </a>
    <?php endforeach ?>
</div>