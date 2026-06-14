<?php
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri      = ($basePath && str_starts_with($uriRaw, $basePath))
            ? substr($uriRaw, strlen($basePath)) : $uriRaw;

$navItems = [
    ['url'=>'/m2',              'label'=>'Sesiones',     'icon'=>'bi-clipboard2-data', 'exacto'=>true],
    ['url'=>'/m2/nueva-sesion', 'label'=>'Nueva sesión', 'icon'=>'bi-plus-circle',     'exacto'=>false],
];
?>
<div style="display:flex;gap:4px;flex-wrap:wrap;background:#f1f5f9;
            border-radius:10px;padding:6px;margin-bottom:20px;width:fit-content">
    <?php foreach ($navItems as $item):
        $active = $item['exacto']
            ? ($uri === '/m2' || $uri === '/m2/')
            : str_starts_with($uri, $item['url']);
    ?>
    <a href="<?= APP_URL . $item['url'] ?>"
        style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;
            border-radius:8px;font-size:13px;
            font-weight:<?= $active ? '700' : '500' ?>;
            text-decoration:none;
            background:<?= $active ? '#091c34' : 'transparent' ?>;
            color:<?= $active ? '#fff' : '#475569' ?>">
        <i class="bi <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
    </a>
    <?php endforeach ?>
</div>