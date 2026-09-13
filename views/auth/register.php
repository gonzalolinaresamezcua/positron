<form class="pg-card pg-form" method="post" action="/registro">
    <?= csrf_field() ?>
    <h1>Crear cuenta</h1>
    <p class="pg-muted">Registro gratuito. Acceso inmediato al chat POSITROM.</p>
    <label class="pg-field">
        <span>Nombre</span>
        <input class="pg-input" type="text" name="name" value="<?= e(old('name')) ?>" required maxlength="120">
    </label>
    <label class="pg-field">
        <span>Correo</span>
        <input class="pg-input" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="username">
    </label>
    <label class="pg-field">
        <span>Contraseña (mín. 10)</span>
        <input class="pg-input" type="password" name="password" required minlength="10" autocomplete="new-password">
    </label>
    <label class="pg-field">
        <span>Confirmar contraseña</span>
        <input class="pg-input" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
    </label>
    <button class="pg-btn pg-btn-ion" type="submit">Registrarme</button>
    <p class="pg-muted">¿Ya tienes cuenta? <a href="/acceso">Acceder</a></p>
</form>
