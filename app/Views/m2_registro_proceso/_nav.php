<?php
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri      = ($basePath && str_starts_with($uriRaw, $basePath))
            ? substr($uriRaw, strlen($basePath)) : $uriRaw;

$navItems = [
    ['url'=>'/m2',              'label'=>'Sesiones',      'icon'=>'📋', 'exacto'=>true],
    ['url'=>'/m2/nueva-sesion', 'label'=>'Nueva sesión',  'icon'=>'➕', 'exacto'=>false],
];
?>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px">
    <?php foreach ($navItems as $item):
        $active = $item['exacto']
            ? ($uri === '/m2' || $uri === '/m2/')
            : str_starts_with($uri, $item['url']);
    ?>
    <a href="<?= APP_URL . $item['url'] ?>"
        style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
            border-radius:8px;font-size:13px;
            font-weight:<?= $active ? '700' : '500' ?>;
            text-decoration:none;
            border:1px solid <?= $active ? '#dc2626' : '#e2e8f0' ?>;
            background:<?= $active ? '#dc2626' : '#fff' ?>;
            color:<?= $active ? '#fff' : '#475569' ?>">
        <?= $item['icon'] ?> <?= $item['label'] ?>
    </a>
    <?php endforeach ?>
</div>