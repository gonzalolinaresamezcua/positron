<section class="pg-stats">
    <article class="pg-stat"><span>Peticiones (mes)</span><strong><?= e((string) $month['requests']) ?></strong></article>
    <article class="pg-stat"><span>Tokens (mes)</span><strong><?= e((string) ((int) $month['tokens_in'] + (int) $month['tokens_out'])) ?></strong></article>
    <article class="pg-stat"><span>Coste estimado</span><strong><?= e(money_eur((float) $month['cost_eur'])) ?></strong></article>
    <article class="pg-stat"><span>Presupuesto / cliente</span><strong><?= e(money_eur((float) $config['budget'])) ?></strong></article>
</section>
<p class="pg-muted">Coste entrada <?= e((string) $config['in']) ?> € / 1M tok · salida <?= e((string) $config['out']) ?> € / 1M tok
    <?php if ($config['allowance']): ?> · tope duro <?= e((string) $config['allowance']) ?> tokens<?php endif; ?></p>
<table class="pg-table">
    <thead><tr><th>Cliente</th><th>Peticiones</th><th>Tokens</th><th>Gasto</th></tr></thead>
    <tbody>
    <?php foreach ($top as $u): ?>
        <tr>
            <td><a href="/admin/clientes/<?= e((string) $u['id']) ?>"><?= e($u['email']) ?></a></td>
            <td><?= e((string) $u['requests']) ?></td>
            <td><?= e((string) $u['tokens']) ?></td>
            <td><?= e(money_eur((float) $u['cost_eur'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
