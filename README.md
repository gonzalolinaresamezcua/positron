# POSITROM

Producto PHP 8 + MySQL para vender **POSITROM**: un chat de IA de suscripción, un solo plan (**12 €/mes**), interfaz en español, listo para Plesk.

El marco visual es **Positrom Galáctico** (verdes y azules, estética galáctica, movimiento propio; no es un restyle de Bootstrap). El emblema es un **rayo de luz gamma** / positrom en SVG.

## Qué incluye

- Web de marketing animada, registro, acceso, checkout Mollie, chat de suscriptor y administración completa.
- Pasarela **Mollie**: primer pago (`sequenceType=first`), mandato, suscripción mensual recurrente, webhooks, activación/cancelación.
- Recordatorio de cobro mensual por **correo SMTP** y tarea cron de Plesk.
- Chat contra el modelo Cursor **composer-2.5** mediante cliente HTTP envolvente (`CURSOR_API_KEY` + URL base configurable).
- Tope mensual de tokens/gasto ligado a los 12 €: el chat se detiene al agotar el presupuesto.
- Admin: claves `.env` (escritura atómica y lista blanca), clientes, pagos, activar/cancelar, costes de token, estadísticas reales (visitas, usuarios, peticiones, tokens, uso).
- Seguridad: PDO preparado, `password_hash`, CSRF, roles, secretos fuera del repo.

Composer **no es obligatorio**. El autoload va en `app/Autoloader.php`.

## Requisitos

- PHP 8.1+ (recomendado 8.3) con `pdo_mysql`, `mbstring`, `json`, `curl`
- MySQL 8 o MariaDB 10.5+
- Apache con `mod_rewrite` (Plesk) o el servidor embebido de PHP para desarrollo
- Extensión `openssl` recomendada para SMTP TLS

## Despliegue en Plesk

1. Sube el código (Git o archivo) **fuera** del docroot público, o deja la raíz del repo en el dominio.
2. En **Hosting Settings**, apunta el document root a la carpeta `public/` del proyecto.
3. Selecciona **PHP 8.2 u 8.3** (FPM o FastCGI).
4. Crea una base de datos MySQL y un usuario con privilegios sobre ella.
5. Copia `.env.example` a `.env` en la raíz del repo (un nivel por encima de `public/`).
6. Rellena `APP_URL` (`https://positrom.com`), `APP_KEY` (cadena larga aleatoria), datos `DB_*`, y deja placeholders de Mollie/Cursor/SMTP hasta tener claves reales.
7. Importa `database/schema.sql` y `database/seed.sql` (phpMyAdmin o `mysql < archivo`).
8. Permisos: `storage/` y `.env` escribibles por el usuario del dominio (`chmod 600 .env`). El admin escribe `.env` de forma segura.
9. Webhook Mollie: `https://positrom.com/webhooks/mollie` (POST, sin CSRF).
10. Tareas programadas: ver `deploy/plesk-cron.example`. Mínimo diario:
    - `cron/payment-reminders.php` (recordatorio de cobro)
    - opcional `cron/subscription-sync.php`
11. En Admin → Ajustes pega `MOLLIE_API_KEY`, `CURSOR_API_KEY`, `CURSOR_API_BASE` y SMTP. No commitees claves reales.
12. Cambia de inmediato la contraseña del admin semilla.

Si el docroot apunta por error a la raíz del repo, el `.htaccess` de la raíz reenvía a `public/`.

## Desarrollo local

```bash
cp .env.example .env
# edita DB_* y APP_URL=http://127.0.0.1:8080
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

Cámbiala tras el primer acceso.

## Pagos Mollie

1. Crear cliente Mollie.
2. Primer pago de 12,00 € con `sequenceType=first`.
3. Tras `paid`, se crea la suscripción `interval=1 month` con `startDate` al mes siguiente (el primer mes ya está cobrado).
4. Los cobros siguientes llegan por el mismo webhook (`id` de pago nuevo + `subscriptionId`).
5. `AUTO_ACTIVATE_ON_PAYMENT=1` abre el chat al cobrar; si vale `0`, el admin activa a mano.

Sin `MOLLIE_API_KEY` real el checkout muestra un error claro (placeholders `test_xxxx…`).

## Chat / Cursor

El cliente `Positrom\Services\CursorClient` llama a:

`POST {CURSOR_API_BASE}{CURSOR_CHAT_PATH}`

por defecto `https://api.cursor.com/v1/chat/completions`, modelo `composer-2.5`, cabecera `Authorization: Bearer {CURSOR_API_KEY}`.

La URL, la ruta y el modelo son configurables en Admin (se guardan en `.env` y `settings`). El SDK oficial de Cursor es de agentes; este envoltorio PHP habla HTTP para el chat de suscriptores. No hace falta una clave real para instalar o verificar la app.

El coste se calcula con € / 1M tokens de entrada y salida. Si el gasto del mes ≥ presupuesto (por defecto 12 €) o se supera el tope opcional de tokens, el chat responde 402 y no llama al modelo.

## Estructura

```
public/          ← document root Plesk
app/             ← núcleo, controladores, servicios
views/           ← plantillas en español
database/        ← schema.sql, seed.sql, migrations/
cron/            ← recordatorios y sync
storage/         ← logs (escribible)
```

## Licencia

MIT © 2026 digitacode.es
