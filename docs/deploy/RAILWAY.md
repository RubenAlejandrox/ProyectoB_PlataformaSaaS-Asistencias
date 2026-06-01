# Despliegue en Railway — GAMA Asistencias

Guía corregida (Composer + PHP en Nixpacks). Sigue el orden de las **Partes 1–5**.

---

## Parte 1 — Archivos en el repositorio (ya incluidos)

| Archivo | Función |
|---------|---------|
| `nixpacks.toml` | PHP 8.3, extensiones, **`phpPackages.composer`**, `npm`, build Vite |
| `railway.json` | Nixpacks, migrate al arrancar, healthcheck `/up` |
| `Procfile` | `web` / `worker` / `reverb` |
| `config/database.php` | `DB_URL`, `sslmode=require` para Supabase |
| `bootstrap/app.php` | `trustProxies(at: '*')` para HTTPS en Railway |

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

# Mail (demo sin SMTP)
MAIL_MAILER=log
```

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
5. **Deploy** del Web → revisar logs (debe verse `composer install` y `Serving on 0.0.0.0:PORT`).
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
| `config:cache` sin APP_KEY | Añade `APP_KEY` en variables del servicio **antes** del build. |
| 419 / sesión | `SESSION_DOMAIN` vacío; `SANCTUM_STATEFUL_DOMAINS` = host Railway sin `https://`. |
| SSL base de datos | `DB_SSLMODE=require` y host Supabase correcto. |
| Assets sin CSS | Revisa log: `npm run build` debe completar sin error. |

---

## Seguridad

No subas al repositorio `.env` con claves reales. Rota credenciales si se compartieron en chat o logs.

---

*G.A.M.A. Solutions — DevOps*
