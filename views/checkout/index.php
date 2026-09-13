<section class="pg-split">
    <article class="pg-card">
        <h1>Activar POSITROM</h1>
        <p>Plan único: <strong><?= e(money_eur((float) $price)) ?></strong> al mes. El primer pago crea el mandato de Mollie y cubre el mes en curso. Los siguientes cobros son automáticos.</p>
        <?php if (($subscription['status'] ?? '') === 'active'): ?>
            <p class="pg-banner pg-banner-ok">Tu suscripción ya está activa.</p>
            <a class="pg-btn pg-btn-ion" href="/chat">Ir al chat</a>
        <?php elseif (($subscription['status'] ?? '') === 'pending_activation'): ?>
            <p class="pg-banner pg-banner-ok">Pago recibido. Un administrador debe activar el acceso.</p>
        <?php else: ?>
            <form method="post" action="/checkout">
                <?= csrf_field() ?>
                <button class="pg-btn pg-btn-ion pg-btn-lg" type="submit">Pagar 12 € y suscribirme</button>
            </form>
        <?php endif; ?>
    </article>
    <aside class="pg-card">
        <h2>Estado</h2>
        <p>Suscripción: <strong><?= e($subscription['status'] ?? 'sin alta') ?></strong></p>
        <p>Próximo cobro: <?= e($subscription['next_payment_date'] ?? '—') ?></p>
    </aside>
</section>
