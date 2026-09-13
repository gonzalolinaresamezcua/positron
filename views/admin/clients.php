<form class="pg-toolbar" method="get" action="/admin/clientes">
    <input class="pg-input" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar nombre o correo">
    <button class="pg-btn pg-btn-ion" type="submit">Buscar</button>
</form>
<table class="pg-table">
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Uso mes</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td>
                <strong><?= e($u['name']) ?></strong><br>
                <span class="pg-muted"><?= e($u['email']) ?></span>
            </td>
            <td><?= e($u['role']) ?><?= !(int) $u['is_active'] ? ' · inactivo' : '' ?></td>
            <td><?= e(money_eur((float) $u['usage']['spent_eur'])) ?> · <?= e((string) $u['usage']['tokens']) ?> tok</td>
            <td><a class="pg-btn pg-btn-ghost pg-btn-sm" href="/admin/clientes/<?= e((string) $u['id']) ?>">Abrir</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="pg-muted"><?= e((string) $total) ?> cuentas</p>
