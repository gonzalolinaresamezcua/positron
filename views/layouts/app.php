<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'POSITROM') . ' · POSITROM') ?></title>
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/positrom-galactico.css">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
</head>
<body class="pg-body pg-body-app">
    <div class="pg-stars pg-stars-dim" aria-hidden="true"></div>
    <header class="pg-nav">
        <a class="pg-brand" href="/">
            <img src="/assets/img/logo-positrom.svg" alt="" width="32" height="32">
            <span>POSITROM</span>
        </a>
        <nav class="pg-nav-links">
            <a href="/chat">Chat</a>
            <a href="/cuenta">Cuenta</a>
            <?php if (is_admin()): ?><a href="/admin">Admin</a><?php endif; ?>
            <form method="post" action="/salida" class="pg-inline">
                <?= csrf_field() ?>
                <button class="pg-btn pg-btn-ghost pg-btn-sm" type="submit">Salir</button>
            </form>
        </nav>
    </header>
    <main class="pg-app-main">
        <?php require POSITROM_VIEWS . '/partials/flash.php'; ?>
        <?= $content ?? '' ?>
    </main>
    <script src="/assets/js/app.js" defer></script>
    <script src="/assets/js/chat.js" defer></script>
</body>
</html>
