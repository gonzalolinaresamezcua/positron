<p class="pg-lead"><?= e((string) $count) ?> pagos cobrados · <?= e(money_eur((float) $revenue)) ?></p>
<table class="pg-table">
    <thead><tr><th>Fecha</th><th>Cliente</th><th>Importe</th><th>Estado</th><th>Método</th><th>Id</th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
        <tr>
            <td><?= e($p['created_at']) ?></td>
            <td><a href="/admin/clientes/<?= e((string) $p['user_id']) ?>"><?= e($p['email']) ?></a></td>
            <td><?= e(money_eur((float) $p['amount_eur'])) ?></td>
            <td><?= e($p['status']) ?></td>
            <td><?= e((string) ($p['method'] ?? '—')) ?></td>
            <td><code><?= e($p['mollie_payment_id']) ?></code></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
