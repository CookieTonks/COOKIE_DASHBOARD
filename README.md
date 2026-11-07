# Recordatorios de pago

Administra clientes, contactos y planes de pago, y envía por correo un recordatorio
**2 días hábiles antes** de cada fecha de pago.

Laravel 12, PHP 8.2+, MySQL. No necesita Node ni compilar assets.

## Cómo funciona

- **Cliente → contactos → planes → cobros.** Un plan define concepto, monto, ciclo y próxima
  fecha; el sistema genera los cobros solo. Tú los marcas como pagados o cancelados.
- **Días hábiles:** excluye sábados, domingos y lo que esté en *Días inhábiles*, que ya trae los
  feriados oficiales de México (LFT art. 74) y se puede editar.
- **Envío:** `reminders:send` corre a las 8:00, 12:00 y 16:00 (hora de México). Las 8:00 es el envío
  normal; las otras dos reintentan fallos y cubren cobros dados de alta en el día.
- **Un correo por cobro**, con todos los contactos marcados como "Recibe recordatorios" en el Para.
  Solo sale en días hábiles y nunca después de la fecha de pago.
- Si el servidor estuvo caído, los recordatorios atrasados salen en la siguiente corrida.
- Los fallos aparecen en *Inicio* y todos los intentos en *Historial*.

## Instalar en servidor con Docker

```bash
cp .env.example .env
```

Edita `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
SESSION_SECURE_COOKIE=true      # solo con HTTPS
DB_PASSWORD=una-contraseña-larga

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME="tucorreo@gmail.com"
MAIL_PASSWORD="contraseña-de-aplicacion"
MAIL_FROM_ADDRESS="tucorreo@gmail.com"
MAIL_FROM_NAME="Tu Empresa"
```

Con `MAIL_MAILER=log` no sale ningún correo: el sistema lo avisa y no marca nada como enviado.

```bash
# Genera la llave y pégala en APP_KEY de .env
docker compose run --rm --no-deps --entrypoint "php artisan key:generate --show" app

docker compose up -d --build
docker compose exec app php artisan app:admin tucorreo@ejemplo.com
docker compose exec app php artisan reminders:test tucorreo@ejemplo.com
```

El contenedor `scheduler` es el que dispara los envíos; si se detiene, no salen recordatorios.
La app queda en el puerto 8080 (`APP_PORT` lo cambia). Ponle HTTPS con tu proxy.

## Instalar en hosting compartido (cPanel + FTP)

Cada `push` a `main` corre las pruebas y, si pasan, sube la app por FTP
(`.github/workflows/deploy.yml`). También se puede lanzar a mano desde la pestaña *Actions*.

**Una sola vez, antes del primer `push`:**

1. **Carpeta destino:** en `deploy.yml`, `server-dir` debe ser la carpeta de tu subdominio.
2. **Secretos del repositorio:** `FTP_SERVER`, `FTP_USER` y `FTP_PASS`, en
   *Settings → Secrets and variables → Actions*.
3. **cPanel:**
   - Crea el subdominio con la raíz en `<carpeta-del-subdominio>/public`.
   - Elige PHP 8.2 o mayor para ese subdominio.
   - Crea la base MySQL y su usuario.
   - Activa HTTPS.

**Una sola vez, después del primer deploy:**

4. **`.env`:** copia `.env.hosting.example` como `.env` en la carpeta de la app y llénalo.
   El deploy nunca lo sube ni lo borra.
5. **Base de datos y usuario.** Sin terminal, usa un cron temporal. Pon tu contraseña en
   `ADMIN_PASSWORD` dentro de `.env` y agrega este cron, cada minuto:
   ```
   cd ~/<carpeta-del-subdominio> && php artisan migrate --force >> storage/logs/instalacion.log 2>&1 && php artisan app:admin tucorreo@ejemplo.com --from-env >> storage/logs/instalacion.log 2>&1
   ```
   Espera un par de minutos y revisa `storage/logs/instalacion.log`. Si dice `Usuario listo`,
   **borra ese cron y la línea `ADMIN_PASSWORD`**, y borra también el log.
   Con terminal o SSH, corre esos comandos a mano; sin `--from-env`, te pide la contraseña en pantalla.
6. **Cron de recordatorios**, cada minuto. Sin él no salen:
   ```
   * * * * * cd ~/<carpeta-del-subdominio> && php artisan schedule:run >> /dev/null 2>&1
   ```
   Si el cron falla, prueba con la ruta completa de PHP que indique tu hosting.
7. **Correo real:** cambia `MAIL_MAILER=smtp` y agrega la contraseña. Para probar sin terminal, usa
   otro cron temporal, y bórralo en cuanto te llegue el correo:
   ```
   cd ~/<carpeta-del-subdominio> && php artisan reminders:test tucorreo@ejemplo.com >> storage/logs/instalacion.log 2>&1
   ```

**En cada actualización:** el `push` sube el código, pero no corre migraciones. Si agregaste
alguna, corre `php artisan migrate --force` en el servidor, con terminal o con un cron temporal.

## Comandos

| Comando | Para qué |
|---|---|
| `php artisan app:admin correo` | Crea un usuario o cambia su contraseña. No hay registro público. |
| `php artisan reminders:test correo` | Envía un correo de prueba para revisar el SMTP. |
| `php artisan reminders:send --dry-run` | Muestra qué se enviaría, sin enviar nada. |
| `php artisan test` | Corre las pruebas. |

## Personalizar

- **Texto del correo:** `resources/views/emails/payment-reminder.blade.php` y su versión `-text`.
- **Días de anticipación:** `REMINDER_BUSINESS_DAYS_BEFORE` en `.env`.
- **Copia oculta para ti:** `REMINDER_BCC="tucorreo@ejemplo.com"` en `.env`. Tus clientes no la ven y
  no aparece en *Historial*. Si el correo está mal escrito, los envíos fallan y lo verás en *Inicio*.
- **Horarios de envío:** `routes/console.php`.
