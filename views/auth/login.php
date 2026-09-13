<form class="pg-card pg-form" method="post" action="/acceso">
    <?= csrf_field() ?>
    <h1>Acceso</h1>
    <label class="pg-field">
        <span>Correo</span>
        <input class="pg-input" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="username">
    </label>
    <label class="pg-field">
        <span>Contraseña</span>
        <input class="pg-input" type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="pg-btn pg-btn-ion" type="submit">Entrar</button>
    <p class="pg-muted">¿Aún no tienes órbita? <a href="/registro">Crear cuenta</a></p>
</form>
