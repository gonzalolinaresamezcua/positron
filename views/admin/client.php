<section class="pg-split">
    <article class="pg-card">
        <h2><?= e($client['name']) ?></h2>
        <p><?= e($client['email']) ?> · <?= e($client['role']) ?></p>
        <p>Alta: <?= e($client['created_at']) ?> · último acceso: <?= e($client['last_login_at'] ?? '—') ?></p>
        <p>Suscripción: <strong><?= e($subscription['status'] ?? 'sin alta') ?></strong></p>
        <p>Mollie customer: <code><?= e($subscription['mollie_customer_id'] ?? '—') ?></code></p>
        <p>Mollie sub: <code><?= e($subscription['mollie_subscription_id'] ?? '—') ?></code></p>
        <div class="pg-actions">
            <form method="post" action="/admin/clientes/<?= e((string) $client['id']) ?>/activar">
                <?= csrf_field() ?>
                <button class="pg-btn pg-btn-ion" type="submit">Activar</button>
            </form>
            <form method="post" action="/admin/clientes/<?= e((string) $client['id']) ?>/cancelar">
                <?= csrf_field() ?>
                <input class="pg-input" type="text" name="reason" placeholder="Motivo de cancelación">
                <button class="pg-btn pg-btn-ghost" type="submit">Cancelar</button>
            </form>
            <?php if ($client['role'] !== 'admin'): ?>
            <form method="post" action="/admin/clientes/<?= e((string) $client['id']) ?>/estado">
                <?= csrf_field() ?>
                <button class="pg-btn pg-btn-ghost" type="submit"><?= (int) $client['is_active'] ? 'Desactivar cuenta' : 'Reactivar cuenta' ?></button>
            </form>
            <?php endif; ?>
        </div>
    </article>
    <article class="pg-card">
        <h2>Tope vs 12 €</h2>
        <p>Gastado: <?= e(money_eur((float) $usage['spent_eur'])) ?> / <?= e(money_eur((float) $usage['budget_eur'])) ?></p>
        <div class="pg-meter"><i style="width: <?= e((string) min(100, $usage['budget_eur'] > 0 ? ($usage['spent_eur'] / $usage['budget_eur']) * 100 : 0)) ?>%"></i></div>
        <p>Tokens <?= e((string) $usage['tokens']) ?> · peticiones <?= e((string) $usage['requests']) ?></p>
        <?php if ($usage['exhausted']): ?><p class="pg-banner pg-banner-err">Cupo agotado.</p><?php endif; ?>
    </article>
</section>
<section class="pg-card">
    <h2>Pagos del cliente</h2>
    <table class="pg-table">
        <thead><tr><th>Fecha</th><th>Importe</th><th>Estado</th><th>Id</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= e($p['created_at']) ?></td>
                <td><?= e(money_eur((float) $p['amount_eur'])) ?></td>
                <td><?= e($p['status']) ?></td>
                <td><code><?= e($p['mollie_payment_id']) ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
