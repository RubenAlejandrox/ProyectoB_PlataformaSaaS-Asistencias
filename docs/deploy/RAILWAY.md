# Despliegue en Railway — GAMA Asistencias

Guía corregida (Composer + PHP en Nixpacks). Sigue el orden de las **Partes 1–5**.

---

## Parte 1 — Archivos en el repositorio (ya incluidos)

| Archivo | Función |
|---------|---------|
| `nixpacks.toml` | PHP 8.3, extensiones, **`phpPackages.composer`**, `npm`, build Vite, `NIXPACKS_PHP_ROOT_DIR` |
| `nginx.template.conf` | Nginx + buffers FastCGI, `client_max_body_size`, timeouts (evita 502/504) |
| `.user.ini` / `public/.user.ini` | `memory_limit`, subidas y `max_execution_time` para PHP |
| `scripts/railway-start.sh` | Arranque **php artisan serve** en `$PORT` (Nixpacks no trae nginx) |
| `scripts/railway-start-nginx.sh` | Opcional: Nginx + PHP-FPM si añades nginx al build |
| `railway.json` | Nixpacks, **preDeploy** migrate, start `railway-start.sh`, healthcheck `/up` |
| `Procfile` | `web` / `worker` / `reverb` |
| `config/database.php` | `DB_URL`, `sslmode=require` para Supabase |
| `bootstrap/app.php` | `trustProxies(at: '*')` para HTTPS en Railway |

### Opción 1 — Nixpacks + `php artisan serve` (recomendado en Railway)

El servicio **Web** arranca con `bash scripts/railway-start.sh` (`php artisan serve --host=0.0.0.0 --port=$PORT`). Nixpacks no instala `nginx` por defecto; si ves `nginx: not found`, confirma que **no** uses `railway-start-nginx.sh` en el start command.

Para Nginx + PHP-FPM añade `nginx` a `nixPkgs` en `nixpacks.toml` y cambia el start command a `bash scripts/railway-start-nginx.sh` (`nginx.template.conf`).

**PHP (`memory_limit`, subidas, tiempo de ejecución)** — archivo `.user.ini` en la raíz (y copia en `public/`). En el build, Nixpacks también lo fusiona al `php.ini` del contenedor. Respaldo en Nginx vía `fastcgi_param PHP_VALUE` en `nginx.template.conf`.

**Nginx (502/504, carga)** — `nginx.template.conf`:

- `client_max_body_size 50M`
- `fastcgi_buffers 16 16k`, `fastcgi_buffer_size 32k`
- `fastcgi_read_timeout 120s`

**PHP-FPM (`pm.max_children`)** — en Railway → servicio **Web** → **Variables**:

| Variable | Valor sugerido | Notas |
|----------|----------------|-------|
| `FPM_MAX_CHILDREN` | `20` | Plan ~512 MB RAM |
| `FPM_MAX_CHILDREN` | `40`–`50` | Plan 1 GB+ RAM (Nixpacks trae 50 por defecto) |

`scripts/railway-start-nginx.sh` aplica `FPM_MAX_CHILDREN` al arrancar si la defines. No subas por encima de lo que permita la RAM (cada worker Laravel suele usar ~80–150 MB).

**Migraciones** — van en **preDeploy** (`railway.json`), no en el loop de arranque de Nginx, para no bloquear cada request mientras migran.

**Desarrollo local** — sigue pudiendo usar `bash scripts/railway-start.sh` (`artisan serve`).

**Importante:** En Railway → servicio Web → **Variables**, define también `APP_KEY` **antes del primer build** (o el `config:cache` del build puede fallar). Usa el valor de `php artisan key:generate --show`.

```bash
git add nixpacks.toml railway.json Procfile config/database.php bootstrap/app.php docs/deploy/
git commit -m "fix: Railway Nixpacks con Composer y PHP 8.3"
git push origin main
```

---

## Parte 2 — Variables de entorno (servicio Web)

Railway → tu servicio **Web** → **Variables**. Sustituye `TU-APP` y credenciales reales.

```env
# App
APP_NAME=GAMA Asistencias
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:PEGAR_RESULTADO_DE_php_artisan_key_generate_show
APP_URL=https://TU-APP.up.railway.app

# Logs
LOG_CHANNEL=stderr
LOG_LEVEL=error

# Base de datos — Supabase (producción)
DB_CONNECTION=pgsql
DB_HOST=db.xxxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=TU_PASSWORD_SUPABASE
DB_SSLMODE=require
# Opcional URL única:
# DB_URL=postgresql://postgres:PASSWORD@db.xxxxx.supabase.co:5432/postgres

# Sesión y caché
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
# Dejar vacío salvo dominio personalizado; si no coincide con el host real → loop de login
SESSION_DOMAIN=
CACHE_STORE=database
QUEUE_CONNECTION=database

# Sanctum (solo host, sin https://)
SANCTUM_STATEFUL_DOMAINS=TU-APP.up.railway.app

# Supabase Storage
SUPABASE_URL=https://xxxxx.supabase.co
SUPABASE_ANON_KEY=tu_anon_key
SUPABASE_SERVICE_KEY=tu_service_role_key
SUPABASE_BUCKET_JUSTIFICATIONS=justificantes-adjuntos
SUPABASE_BUCKET_INSTITUTION_LOGOS=institution-logos

# PayPal (nombres del proyecto: config/paypal.php)
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=tu_client_id
PAYPAL_SECRET=tu_secret
PAYPAL_CURRENCY=MXN
PAYPAL_LOCALE=es-MX

# Reverb (después de crear servicio Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=tu_app_id
REVERB_APP_KEY=tu_app_key
REVERB_APP_SECRET=tu_app_secret
REVERB_HOST=TU-REVERB.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Mail
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx
# Si en Railway solo existe RESEND_KEY, renómbrala a RESEND_API_KEY o deja ambas (config acepta fallback).
```

`SESSION_STORE` y `SESSION_CONNECTION` vacíos son normales (Laravel usa defaults).

### Servicio Worker

- **Mismas variables** que el Web.
- **Start command:**
  ```bash
  php artisan queue:work --sleep=3 --tries=3 --timeout=90
  ```

### Servicio Reverb

- **Mismas variables** que el Web.
- **Start command:**
  ```bash
  php artisan reverb:start --host=0.0.0.0 --port=$PORT
  ```
- **Settings** → **Networking** → **Generate Domain** → copia el host en `REVERB_HOST` del servicio Web → **Redeploy** Web.

> Sin Reverb: `BROADCAST_CONNECTION=log` (asistencia docente usa polling).

---

## Parte 3 — Generar `APP_KEY`

En tu PC (en la carpeta del proyecto):

```bash
php artisan key:generate --show
```

Copia `base64:...` → variable `APP_KEY` en Railway (Web, Worker y Reverb).

---

## Parte 4 — Supabase (sesiones y caché)

Lo normal es que **`php artisan migrate`** cree `sessions`, `cache`, etc.

Si hace falta manualmente, ejecuta en **Supabase → SQL Editor**:

`docs/deploy/supabase-railway-tablas.sql`

---

## Parte 5 — Orden de deploy

1. Push del código con `nixpacks.toml` actualizado (incluye **Composer**).
2. Railway → **New Project** → repo GitHub.
3. Servicio **Web** → pegar variables (con `APP_KEY`).
4. **Networking** → **Generate Domain** → actualizar `APP_URL` y `SANCTUM_STATEFUL_DOMAINS`.
5. **Deploy** del Web → revisar logs (`composer install`, `npm run build`, `artisan serve` en `$PORT`, sin error `nginx: not found`).
6. Probar: `https://TU-APP.up.railway.app/up` → debe responder OK.
7. Crear servicio **Worker** (mismas vars + start command).
8. Crear servicio **Reverb** → dominio → actualizar `REVERB_HOST` en Web → redeploy.
9. Seeders iniciales (una vez), shell Railway o local contra Supabase:
   ```bash
   php artisan db:seed --class=PlansSeeder --force
   php artisan db:seed --class=RolesSeeder --force
   ```

---

## Si el build falla otra vez

| Error | Qué hacer |
|-------|-----------|
| `composer: command not found` | Confirma que `phpPackages.composer` está en `nixpacks.toml` y haz **Redeploy** limpio. |
| `undefined variable 'npm'` | No pongas `npm` en `nixPkgs`; usa solo `nodejs_20` (npm ya viene incluido). |
| `config:cache` sin APP_KEY | Añade `APP_KEY` en variables del servicio **antes** del build. |
| 500 al guardar sesión | `sessions.user_id` debe ser **uuid** si `users.id` es uuid. Corre migraciones o `docs/deploy/supabase-railway-tablas.sql`. |
| Loop login tras POST /login | Sesión en BD pero cookie no llega: vacía `SESSION_DOMAIN` o alinea con host; `APP_URL=https://TU-APP...`; no uses `config:cache` con vars viejas. |
| 419 / sesión | `SANCTUM_STATEFUL_DOMAINS` = host sin `https://`; `SESSION_SECURE_COOKIE=true` en HTTPS. |
| `nginx: not found` | Start command = `bash scripts/railway-start.sh` (ver `railway.json` / Procfile). |
| Resend sin enviar | Variable `RESEND_API_KEY` (no solo `RESEND_KEY`). |
| SSL base de datos | `DB_SSLMODE=require` y host Supabase correcto. |
| Assets sin CSS | Revisa log: `npm run build` debe completar sin error. |
| 502 / 504 en picos de tráfico | Sube `FPM_MAX_CHILDREN` según RAM; revisa `nginx.template.conf` (timeouts/buffers). |
| Subida de archivo &gt; 1 MB falla | Confirma `.user.ini` y `client_max_body_size 50M` en `nginx.template.conf`. |
| Sigue saliendo `nginx: not found` | Railway → **Settings** → Start command = `bash scripts/railway-start.sh` o vacío si usas `railway.json`. |

---

## Seguridad

No subas al repositorio `.env` con claves reales. Rota credenciales si se compartieron en chat o logs.

---

*G.A.M.A. Solutions — DevOps*
