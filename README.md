# POSITROM

Plataforma **gratuita y autoalojada** de chat IA con **OpenAI** (`gpt-6-astra`). PHP 8 + MySQL, interfaz en español, lista para Plesk.

El marco visual es **Positrom Galáctico** (verdes y azules, estética galáctica). El emblema es un rayo de luz gamma / positrom en SVG.

## Qué incluye

- Web de marketing, registro público gratuito, acceso, chat y panel de administración.
- Chat contra **OpenAI** mediante cliente HTTP (`OPENAI_API_KEY` en `.env` o Admin → Ajustes).
- Modelo por defecto: **gpt-6-astra**.
- Estadísticas de tokens y coste estimado; tope mensual de tokens **opcional** (admin).
- Admin: claves `.env` (escritura atómica), usuarios, uso, tokens.
- Un único administrador (semilla en `database/seed.sql`).
- Seguridad: PDO preparado, `password_hash`, CSRF, roles, secretos fuera del repo.

Composer **no es obligatorio**. El autoload va en `app/Autoloader.php`.

## Requisitos

- PHP 8.1+ (recomendado 8.3) con `pdo_mysql`, `mbstring`, `json`, `curl`
- MySQL 8 o MariaDB 10.5+
- Apache con `mod_rewrite` (Plesk) o el servidor embebido de PHP para desarrollo
- Cuenta OpenAI con API key válida (la aporta quien despliega)

## Despliegue en Plesk

1. Sube el código (Git o archivo). El document root debe ser `public/`.
2. En **Hosting Settings**, apunta el document root a la carpeta `public/` del proyecto.
3. Selecciona **PHP 8.2 u 8.3** (FPM o FastCGI).
4. Crea una base de datos MySQL y un usuario con privilegios sobre ella.
5. Copia `.env.example` a `.env` en la raíz del repo (un nivel por encima de `public/`).
6. Rellena `APP_URL`, `APP_KEY` (cadena larga aleatoria), datos `DB_*` y **`OPENAI_API_KEY`**.
7. Importa `database/schema.sql` y `database/seed.sql` (phpMyAdmin o `mysql < archivo`).
   - Si migras desde la versión de pago antigua, ejecuta también `database/migrations/002_drop_billing.sql`.
8. Permisos: `storage/` y `.env` escribibles por el usuario del dominio (`chmod 600 .env`).
9. En Admin → Ajustes puedes editar `OPENAI_API_KEY` y SMTP. **No commitees claves reales.**
10. Cambia de inmediato la contraseña del admin semilla.

Si el docroot apunta por error a la raíz del repo, el `.htaccess` de la raíz reenvía a `public/`.

No se requieren tareas cron de pagos.

## Desarrollo local

```bash
cp .env.example .env
# edita DB_* , APP_URL=http://127.0.0.1:8080 y OPENAI_API_KEY
mysql -u root < database/schema.sql
mysql -u root positrom < database/seed.sql
php -S 127.0.0.1:8080 -t public public/router.php
```

Verificación:

```bash
php scripts/verify.php
```

## Admin semilla

Documentado **solo aquí**, no en la interfaz:

- Correo: `admin@positrom.local`
- Contraseña: `Positrom#Admin2026`

Cámbiala tras el primer acceso. Solo puede existir **un** administrador.

## Chat / OpenAI

El cliente `Positrom\Services\OpenAIClient` llama a:

`POST {OPENAI_API_BASE}{OPENAI_CHAT_PATH}`

por defecto `https://api.openai.com/v1/chat/completions`, modelo `gpt-6-astra`, cabecera `Authorization: Bearer {OPENAI_API_KEY}`.

La URL, la ruta y el modelo son configurables en Admin (se guardan en `.env` y `settings`).

El coste se calcula con € / 1M tokens de entrada y salida para estadísticas. Si el admin fija `MONTHLY_TOKEN_ALLOWANCE`, el chat se detiene al superar ese tope.

## Variables `.env` principales

| Variable | Descripción |
|----------|-------------|
| `APP_URL` | URL pública del sitio |
| `APP_KEY` | Clave interna de la app |
| `DB_*` | Conexión MySQL |
| `OPENAI_API_KEY` | **Obligatoria** para el chat |
| `OPENAI_MODEL` | Por defecto `gpt-6-astra` |
| `MONTHLY_TOKEN_ALLOWANCE` | Tope opcional de tokens/mes (vacío = sin tope) |

## Estructura

```
public/          ← document root Plesk
app/             ← núcleo, controladores, servicios
views/           ← plantillas en español
database/        ← schema.sql, seed.sql, migrations/
storage/         ← logs (escribible)
```

## Licencia

MIT © 2026 digitacode.es
