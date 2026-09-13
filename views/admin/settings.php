<section class="pg-split">
    <form class="pg-card pg-form" method="post" action="/admin/ajustes/claves">
        <?= csrf_field() ?>
        <h2>Claves (.env)</h2>
        <p class="pg-muted">Escritura atómica y lista blanca. Deja en blanco un secreto para no cambiarlo. No se muestran valores completos.</p>
        <label class="pg-field"><span>APP_URL</span><input class="pg-input" name="APP_URL" value="<?= e($env['APP_URL']) ?>"></label>
        <label class="pg-field"><span>MOLLIE_API_KEY</span><input class="pg-input" name="MOLLIE_API_KEY" value="" placeholder="<?= e(mask_secret($env['MOLLIE_API_KEY'])) ?>" autocomplete="off"></label>
        <label class="pg-field"><span>CURSOR_API_KEY</span><input class="pg-input" name="CURSOR_API_KEY" value="" placeholder="<?= e(mask_secret($env['CURSOR_API_KEY'])) ?>" autocomplete="off"></label>
        <label class="pg-field"><span>CURSOR_API_BASE</span><input class="pg-input" name="CURSOR_API_BASE" value="<?= e($env['CURSOR_API_BASE']) ?>"></label>
        <label class="pg-field"><span>CURSOR_MODEL</span><input class="pg-input" name="CURSOR_MODEL" value="<?= e($env['CURSOR_MODEL']) ?>"></label>
        <label class="pg-field"><span>CURSOR_CHAT_PATH</span><input class="pg-input" name="CURSOR_CHAT_PATH" value="<?= e($env['CURSOR_CHAT_PATH']) ?>"></label>
        <label class="pg-field"><span>SMTP_HOST</span><input class="pg-input" name="SMTP_HOST" value="<?= e($env['SMTP_HOST']) ?>"></label>
        <label class="pg-field"><span>SMTP_PORT</span><input class="pg-input" name="SMTP_PORT" value="<?= e((string) $env['SMTP_PORT']) ?>"></label>
        <label class="pg-field"><span>SMTP_USER</span><input class="pg-input" name="SMTP_USER" value="<?= e($env['SMTP_USER']) ?>"></label>
        <label class="pg-field"><span>SMTP_PASS</span><input class="pg-input" type="password" name="SMTP_PASS" value="" placeholder="<?= e(mask_secret($env['SMTP_PASS'])) ?>" autocomplete="new-password"></label>
        <label class="pg-field"><span>SMTP_FROM</span><input class="pg-input" name="SMTP_FROM" value="<?= e($env['SMTP_FROM']) ?>"></label>
        <label class="pg-field"><span>SMTP_FROM_NAME</span><input class="pg-input" name="SMTP_FROM_NAME" value="<?= e($env['SMTP_FROM_NAME']) ?>"></label>
        <label class="pg-field"><span>SMTP_ENCRYPTION</span>
            <select class="pg-input" name="SMTP_ENCRYPTION">
                <option value="tls" <?= $env['SMTP_ENCRYPTION'] === 'tls' ? 'selected' : '' ?>>tls</option>
                <option value="ssl" <?= $env['SMTP_ENCRYPTION'] === 'ssl' ? 'selected' : '' ?>>ssl</option>
                <option value="none" <?= $env['SMTP_ENCRYPTION'] === 'none' ? 'selected' : '' ?>>none</option>
            </select>
        </label>
        <button class="pg-btn pg-btn-ion" type="submit">Guardar claves</button>
    </form>

    <form class="pg-card pg-form" method="post" action="/admin/ajustes/uso">
        <?= csrf_field() ?>
        <h2>Tope de tokens vs 12 €</h2>
        <p class="pg-muted">El precio comercial del plan es fijo: 12 €. Aquí defines cuánto coste de modelo cabe en ese mes. Al llegar al tope, el chat se detiene.</p>
        <label class="pg-field"><span>Presupuesto mensual (€)</span>
            <input class="pg-input" name="monthly_budget_eur" value="<?= e((string) ($settings['usage.monthly_budget_eur'] ?? '12.00')) ?>" required>
        </label>
        <label class="pg-field"><span>Coste entrada € / 1M tokens</span>
            <input class="pg-input" name="token_input_cost" value="<?= e((string) ($settings['usage.token_input_cost_eur_per_1m'] ?? '0.50')) ?>" required>
        </label>
        <label class="pg-field"><span>Coste salida € / 1M tokens</span>
            <input class="pg-input" name="token_output_cost" value="<?= e((string) ($settings['usage.token_output_cost_eur_per_1m'] ?? '2.50')) ?>" required>
        </label>
        <label class="pg-field"><span>Tope duro de tokens (opcional)</span>
            <input class="pg-input" name="monthly_token_allowance" value="<?= e((string) ($settings['usage.monthly_token_allowance'] ?? '')) ?>" placeholder="vacío = solo presupuesto €">
        </label>
        <label class="pg-field"><span>Días de antelación del recordatorio</span>
            <input class="pg-input" name="reminder_days" value="<?= e((string) ($settings['billing.reminder_days_before'] ?? '3')) ?>">
        </label>
        <label class="pg-check">
            <input type="checkbox" name="auto_activate" value="1" <?= (($settings['billing.auto_activate_on_payment'] ?? '1') === '1') ? 'checked' : '' ?>>
            Activar automáticamente al cobrar
        </label>
        <button class="pg-btn pg-btn-ion" type="submit">Guardar política de uso</button>
    </form>
</section>
