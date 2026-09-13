<section class="pg-split">
    <form class="pg-card pg-form" method="post" action="/admin/ajustes/claves">
        <?= csrf_field() ?>
        <h2>Claves (.env)</h2>
        <p class="pg-muted">Escritura atómica y lista blanca. Deja en blanco un secreto para no cambiarlo. No se muestran valores completos.</p>
        <label class="pg-field"><span>APP_URL</span><input class="pg-input" name="APP_URL" value="<?= e($env['APP_URL']) ?>"></label>
        <label class="pg-field"><span>OPENAI_API_KEY</span><input class="pg-input" name="OPENAI_API_KEY" value="" placeholder="<?= e(mask_secret($env['OPENAI_API_KEY'])) ?>" autocomplete="off"></label>
        <label class="pg-field"><span>OPENAI_API_BASE</span><input class="pg-input" name="OPENAI_API_BASE" value="<?= e($env['OPENAI_API_BASE']) ?>" placeholder="https://api.openai.com"></label>
        <label class="pg-field"><span>OPENAI_MODEL</span><input class="pg-input" name="OPENAI_MODEL" value="<?= e($env['OPENAI_MODEL']) ?>"></label>
        <label class="pg-field"><span>OPENAI_CHAT_PATH</span><input class="pg-input" name="OPENAI_CHAT_PATH" value="<?= e($env['OPENAI_CHAT_PATH']) ?>"></label>
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
        <h2>Estadísticas y tope opcional</h2>
        <p class="pg-muted">Los costes sirven para estadísticas en el panel. El tope de tokens es opcional; vacío = sin límite mensual.</p>
        <label class="pg-field"><span>Coste entrada € / 1M tokens</span>
            <input class="pg-input" name="token_input_cost" value="<?= e((string) ($settings['usage.token_input_cost_eur_per_1m'] ?? '0.50')) ?>" required>
        </label>
        <label class="pg-field"><span>Coste salida € / 1M tokens</span>
            <input class="pg-input" name="token_output_cost" value="<?= e((string) ($settings['usage.token_output_cost_eur_per_1m'] ?? '2.50')) ?>" required>
        </label>
        <label class="pg-field"><span>Tope mensual de tokens (opcional)</span>
            <input class="pg-input" name="monthly_token_allowance" value="<?= e((string) ($settings['usage.monthly_token_allowance'] ?? '')) ?>" placeholder="vacío = sin tope">
        </label>
        <button class="pg-btn pg-btn-ion" type="submit">Guardar política de uso</button>
    </form>
</section>
