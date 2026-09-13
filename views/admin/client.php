<section class="pg-split">
    <article class="pg-card">
        <h2><?= e($client['name']) ?></h2>
        <p><?= e($client['email']) ?> · <?= e($client['role']) ?></p>
        <p>Alta: <?= e($client['created_at']) ?> · último acceso: <?= e($client['last_login_at'] ?? '—') ?></p>
        <p>Estado: <strong><?= (int) $client['is_active'] ? 'activa' : 'inactiva' ?></strong></p>
        <div class="pg-actions">
            <?php if ($client['role'] !== 'admin'): ?>
            <form method="post" action="/admin/clientes/<?= e((string) $client['id']) ?>/estado">
                <?= csrf_field() ?>
                <button class="pg-btn pg-btn-ghost" type="submit"><?= (int) $client['is_active'] ? 'Desactivar cuenta' : 'Reactivar cuenta' ?></button>
            </form>
            <?php endif; ?>
        </div>
    </article>
    <article class="pg-card">
        <h2>Uso del mes</h2>
        <p>Gastado (est.): <?= e(money_eur((float) $usage['spent_eur'])) ?></p>
        <p>Tokens <?= e((string) $usage['tokens']) ?> · peticiones <?= e((string) $usage['requests']) ?></p>
        <?php if ($usage['exhausted']): ?><p class="pg-banner pg-banner-err">Tope de tokens alcanzado.</p><?php endif; ?>
    </article>
</section>
