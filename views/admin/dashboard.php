<?php
$maxVisits = 1;
foreach ($stats['visits_daily'] as $d) {
    $maxVisits = max($maxVisits, (int) $d['visits']);
}
$maxTok = 1;
foreach ($stats['usage_daily'] as $d) {
    $maxTok = max($maxTok, (int) $d['tokens']);
}
?>
<section class="pg-stats">
    <article class="pg-stat"><span>Visitas</span><strong><?= e((string) $stats['visits_all']) ?></strong><small>hoy <?= e((string) $stats['visits_today']) ?> · 7d <?= e((string) $stats['visits_7d']) ?></small></article>
    <article class="pg-stat"><span>Usuarios</span><strong><?= e((string) $stats['users']) ?></strong><small><?= e((string) $stats['active_users']) ?> activos</small></article>
    <article class="pg-stat"><span>Peticiones</span><strong><?= e((string) $stats['requests_all']) ?></strong><small>mes <?= e((string) $stats['requests_month']) ?></small></article>
    <article class="pg-stat"><span>Tokens</span><strong><?= e((string) $stats['tokens_all']) ?></strong><small>mes <?= e((string) $stats['tokens_month']) ?> · <?= e(money_eur((float) $stats['usage_cost_month'])) ?></small></article>
</section>

<section class="pg-split">
    <article class="pg-card">
        <h2>Visitas (14 días)</h2>
        <div class="pg-bars" aria-label="Visitas diarias">
            <?php foreach ($stats['visits_daily'] as $d): ?>
                <div class="pg-bar">
                    <i style="height: <?= e((string) max(6, ((int) $d['visits'] / $maxVisits) * 100)) ?>%"></i>
                    <span><?= e(substr((string) $d['day'], 5)) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if ($stats['visits_daily'] === []): ?><p class="pg-muted">Aún no hay visitas registradas.</p><?php endif; ?>
        </div>
    </article>
    <article class="pg-card">
        <h2>Tokens (14 días)</h2>
        <div class="pg-bars pg-bars-ion" aria-label="Tokens diarios">
            <?php foreach ($stats['usage_daily'] as $d): ?>
                <div class="pg-bar">
                    <i style="height: <?= e((string) max(6, ((int) $d['tokens'] / $maxTok) * 100)) ?>%"></i>
                    <span><?= e(substr((string) $d['day'], 5)) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if ($stats['usage_daily'] === []): ?><p class="pg-muted">Sin uso de modelo todavía.</p><?php endif; ?>
        </div>
    </article>
</section>

<section class="pg-split">
    <article class="pg-card">
        <h2>Rutas más vistas</h2>
        <table class="pg-table">
            <tbody>
            <?php foreach ($stats['top_paths'] as $p): ?>
                <tr><td><?= e($p['path']) ?></td><td><?= e((string) $p['visits']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
    <article class="pg-card">
        <h2>Usuarios por gasto del mes</h2>
        <table class="pg-table">
            <thead><tr><th>Usuario</th><th>Tokens</th><th>Gasto est.</th></tr></thead>
            <tbody>
            <?php foreach ($stats['top_users'] as $u): ?>
                <tr>
                    <td><a href="/admin/clientes/<?= e((string) $u['id']) ?>"><?= e($u['email']) ?></a></td>
                    <td><?= e((string) $u['tokens']) ?></td>
                    <td><?= e(money_eur((float) $u['cost_eur'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>
