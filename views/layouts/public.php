<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'POSITRON') . ' · POSITRON') ?></title>
    <meta name="description" content="POSITRON: chat de IA con suscripción mensual de 12 €. Un plan. Modelo composer-2.5.">
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <meta property="og:title" content="POSITRON">
    <meta property="og:description" content="Chat de IA en órbita. 12 € al mes.">
    <meta property="og:image" content="<?= e(url('/assets/img/logo-positron.png')) ?>">
    <link rel="stylesheet" href="/assets/css/positron-galactico.css">
</head>
<body class="pg-body">
    <div class="pg-stars" aria-hidden="true"></div>
    <div class="pg-nebula" aria-hidden="true"></div>
    <header class="pg-nav">
        <a class="pg-brand" href="/">
            <img src="/assets/img/logo-positron.svg" alt="" width="36" height="36">
            <span>POSITRON</span>
        </a>
        <nav class="pg-nav-links">
            <a href="/#orbita">El plan</a>
            <a href="/#como">Cómo funciona</a>
            <?php if (current_user()): ?>
                <a href="/chat">Chat</a>
                <a href="/cuenta">Cuenta</a>
                <?php if (is_admin()): ?><a href="/admin">Admin</a><?php endif; ?>
            <?php else: ?>
                <a href="/acceso">Acceder</a>
                <a class="pg-btn pg-btn-ion pg-btn-sm" href="/registro">Empezar</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <?php require POSITRON_VIEWS . '/partials/flash.php'; ?>
        <?= $content ?? '' ?>
    </main>
    <footer class="pg-foot">
        <div>
            <strong>POSITRON</strong>
            <span>Chat de IA · 12 €/mes · rayo gamma</span>
        </div>
        <nav>
            <a href="/privacidad">Privacidad</a>
            <a href="/terminos">Términos</a>
        </nav>
    </footer>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
