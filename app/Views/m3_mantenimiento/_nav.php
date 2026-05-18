<?php
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri      = ($basePath && str_starts_with($uriRaw, $basePath))
            ? substr($uriRaw, strlen($basePath)) : $uriRaw;

$navItems = [
    ['url'=>'/m3',           'label'=>'Registros',  'icon'=>'🔧', 'exacto'=>true],
    ['url'=>'/m3/nuevo',     'label'=>'Nuevo',       'icon'=>'➕', 'exacto'=>false],
    ['url'=>'/m3/alertas',   'label'=>'Alertas',     'icon'=>'⚠️', 'exacto'=>false],
    ['url'=>'/m3/calendario','label'=>'Calendario',  'icon'=>'📅', 'exacto'=>false],
];
?>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px">
    <?php foreach ($navItems as $item):
        $active = $item['exacto']
            ? ($uri === '/m3' || $uri === '/m3/')
            : str_starts_with($uri, $item['url']);
    ?>
    <a href="<?= APP_URL . $item['url'] ?>"
        style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
                border-radius:8px;font-size:13px;
                font-weight:<?= $active ? '700' : '500' ?>;
                text-decoration:none;
                border:1px solid <?= $active ? '#7c3aed' : '#e2e8f0' ?>;
                background:<?= $active ? '#7c3aed' : '#fff' ?>;
                color:<?= $active ? '#fff' : '#475569' ?>">
        <?= $item['icon'] ?> <?= $item['label'] ?>
    </a>
    <?php endforeach ?>
</div>