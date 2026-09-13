<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Admin') . ' · Admin POSITRON') ?></title>
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/positron-galactico.css">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
</head>
<body class="pg-body pg-body-admin">
    <aside class="pg-side">
        <a class="pg-brand" href="/admin">
            <img src="/assets/img/logo-positron.svg" alt="" width="32" height="32">
            <span>POSITRON</span>
        </a>
        <nav>
            <a href="/admin">Panel</a>
            <a href="/admin/clientes">Clientes</a>
            <a href="/admin/pagos">Pagos</a>
            <a href="/admin/uso">Tokens y gasto</a>
            <a href="/admin/ajustes">Ajustes</a>
            <a href="/chat">Ir al chat</a>
            <a href="/">Web pública</a>
        </nav>
        <form method="post" action="/salida">
            <?= csrf_field() ?>
            <button class="pg-btn pg-btn-ghost pg-btn-sm" type="submit">Salir</button>
        </form>
    </aside>
    <div class="pg-admin-main">
        <header class="pg-admin-bar">
            <h1><?= e($title ?? 'Admin') ?></h1>
            <span class="pg-pill">12 € / mes</span>
        </header>
        <?php require POSITRON_VIEWS . '/partials/flash.php'; ?>
        <?= $content ?? '' ?>
    </div>
    <script src="/assets/js/app.js" defer></script>
    <script src="/assets/js/admin.js" defer></script>
</body>
</html>
