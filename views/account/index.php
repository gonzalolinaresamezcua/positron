<section class="pg-split">
    <article class="pg-card">
        <h1><?= e($user['name']) ?></h1>
        <p class="pg-muted"><?= e($user['email']) ?></p>
        <p>Cuenta: <span class="pg-pill"><?= (int) $user['is_active'] ? 'activa' : 'inactiva' ?></span></p>
        <p class="pg-muted">POSITROM es gratuito. El consumo del modelo lo cubre quien administra el servidor con su clave OpenAI.</p>
    </article>
    <article class="pg-card">
        <h2>Uso de este mes</h2>
        <p>Coste estimado: <?= e(money_eur((float) $usage['spent_eur'])) ?></p>
        <p>Tokens: <?= e((string) $usage['tokens']) ?><?php if ($usage['token_allowance']): ?> / <?= e((string) $usage['token_allowance']) ?><?php endif; ?></p>
        <p>Peticiones: <?= e((string) $usage['requests']) ?></p>
        <?php if ($usage['exhausted']): ?>
            <p class="pg-banner pg-banner-err">Cupo de tokens agotado. Contacta con administración si necesitas más.</p>
        <?php endif; ?>
    </article>
</section>
