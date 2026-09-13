<section class="pg-split">
    <article class="pg-card">
        <h1><?= e($user['name']) ?></h1>
        <p class="pg-muted"><?= e($user['email']) ?></p>
        <h2>Suscripción</h2>
        <p>Estado: <span class="pg-pill"><?= e($subscription['status'] ?? 'sin alta') ?></span></p>
        <p>Periodo: <?= e($subscription['current_period_start'] ?? '—') ?> → <?= e($subscription['current_period_end'] ?? '—') ?></p>
        <p>Próximo cobro: <?= e($subscription['next_payment_date'] ?? '—') ?></p>
        <?php if (($subscription['status'] ?? '') === 'active'): ?>
            <form method="post" action="/cuenta/cancelar" onsubmit="return confirm('¿Cancelar la suscripción POSITRON?');">
                <?= csrf_field() ?>
                <button class="pg-btn pg-btn-ghost" type="submit">Cancelar suscripción</button>
            </form>
        <?php else: ?>
            <a class="pg-btn pg-btn-ion" href="/checkout">Activar plan</a>
        <?php endif; ?>
    </article>
    <article class="pg-card">
        <h2>Uso de este mes</h2>
        <p><?= e(money_eur((float) $usage['spent_eur'])) ?> de <?= e(money_eur((float) $usage['budget_eur'])) ?></p>
        <div class="pg-meter" role="progressbar" aria-valuenow="<?= e((string) $usage['spent_eur']) ?>" aria-valuemax="<?= e((string) $usage['budget_eur']) ?>">
            <i style="width: <?= e((string) min(100, $usage['budget_eur'] > 0 ? ($usage['spent_eur'] / $usage['budget_eur']) * 100 : 0)) ?>%"></i>
        </div>
        <p>Tokens: <?= e((string) $usage['tokens']) ?><?php if ($usage['token_allowance']): ?> / <?= e((string) $usage['token_allowance']) ?><?php endif; ?></p>
        <p>Peticiones: <?= e((string) $usage['requests']) ?></p>
        <?php if ($usage['exhausted']): ?>
            <p class="pg-banner pg-banner-err">Cupo agotado. El chat está detenido hasta el próximo ciclo.</p>
        <?php endif; ?>
    </article>
</section>
<section class="pg-card">
    <h2>Pagos</h2>
    <table class="pg-table">
        <thead><tr><th>Fecha</th><th>Importe</th><th>Estado</th><th>Mollie</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= e($p['created_at']) ?></td>
                <td><?= e(money_eur((float) $p['amount_eur'])) ?></td>
                <td><?= e($p['status']) ?></td>
                <td><code><?= e($p['mollie_payment_id']) ?></code></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($payments === []): ?>
            <tr><td colspan="4">Aún no hay pagos.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
