<?php /* error_404.php */ ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>404 — SIACEP</title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css"></head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9">
<div class="error-page">
    <div class="error-code">404</div>
    <div class="error-title">Página no encontrada</div>
    <div class="error-desc">La URL solicitada no existe en el sistema.</div>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-primary mt-3">Volver al inicio</a>
</div>
</body></html>