<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIACEP — Acceso al sistema</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body class="auth-body">

<?php
use App\Core\Controller;
$flashes   = Controller::getFlash();
$csrfToken = Controller::csrfToken();
?>

<?php if (!empty($flashes)): ?>
<div class="flash-container auth-flash">
    <?php foreach ($flashes as $flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>">
        <?= htmlspecialchars($flash['message']) ?>
        <button class="flash-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>
<?php /** @var string $content */ ?>
<?= $content ?>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>