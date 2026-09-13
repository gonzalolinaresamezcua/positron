<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Acceso') . ' · POSITROM') ?></title>
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/positrom-galactico.css">
</head>
<body class="pg-body pg-body-auth">
    <div class="pg-stars" aria-hidden="true"></div>
    <div class="pg-auth-wrap">
        <a class="pg-brand pg-brand-center" href="/">
            <img src="/assets/img/logo-positrom.svg" alt="" width="48" height="48">
            <span>POSITROM</span>
        </a>
        <?php require POSITROM_VIEWS . '/partials/flash.php'; ?>
        <?= $content ?? '' ?>
    </div>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
