# Despliegue en Railway — GAMA Asistencias (Laravel 12)

Guía paso a paso para publicar **ProyectoB_PlataformaSaaS-Asistencias** en [Railway](https://railway.app).

---

## 1. Requisitos previos

| Requisito | Detalle |
|-----------|---------|
| Cuenta Railway | [railway.app](https://railway.app) — inicio de sesión con **GitHub** |
| Repositorio | Código en GitHub (rama `main` o la que uses para producción) |
| Base de datos | **PostgreSQL** (plugin Railway **o** Supabase externo) |
| Crédito | ~**5 USD/mes** gratis — suficiente para demo/desarrollo |
| Archivos en el repo | `Procfile`, `nixpacks.toml`, `railway.json`, `scripts/railway-start.sh` (ya incluidos) |

---

## 2. Archivos de despliegue (en la raíz del proyecto)

```
ProyectoB_PlataformaSaaS-Asistencias/
├── Procfile              # web + worker + reverb (servicios separados en Railway)
├── nixpacks.toml         # PHP 8.3, extensiones, composer, npm build
├── railway.json          # builder Nixpacks + migrate + arranque
└── scripts/
    └── railway-start.sh  # cache + php artisan serve en $PORT
```

---

## 3. Paso a paso en Railway

### Paso 1 — Subir cambios a GitHub

```bash
git add Procfile nixpacks.toml railway.json scripts/railway-start.sh docs/deploy/RAILWAY.md
git commit -m "chore: configuración de despliegue Railway"
git push origin main
```

### Paso 2 — Crear proyecto

1. Entra a **Railway** → **New Project**.
2. Elige **Deploy from GitHub repo**.
3. Autoriza GitHub y selecciona `ProyectoB_PlataformaSaaS-Asistencias`.
4. Railway detectará **Nixpacks** gracias a `nixpacks.toml` / `railway.json`.

### Paso 3 — Base de datos PostgreSQL

**Opción A — PostgreSQL en Railway (recomendado para empezar)**

1. En el proyecto → **+ New** → **Database** → **PostgreSQL**.
2. Abre el servicio Postgres → pestaña **Variables** o **Connect**.
3. Copia la URL de conexión (formato `postgresql://...`).

**Opción B — Supabase (ya usado en desarrollo)**

Usa la connection string de Supabase (Session mode o Transaction pooler según tu plan).

En el **servicio web** (Laravel), define:

| Variable | Valor |
|----------|--------|
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | *(URL completa — en Railway: referencia `${{Postgres.DATABASE_URL}}`)* |

Laravel 12 usa la variable **`DB_URL`** (no `DATABASE_URL`). Si Railway solo expone `DATABASE_URL`, crea `DB_URL` con el mismo valor.

Si usas variables sueltas en lugar de URL:

```
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

### Paso 4 — Variables de entorno (servicio Web)

En el servicio de la app → **Variables** → añade o referencia:

#### Obligatorias

```env
APP_NAME="GAMA Asistencias"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...   # generar con: php artisan key:generate --show
APP_URL=https://TU-DOMINIO.up.railway.app

LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}   # si usas Postgres de Railway

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database

SANCTUM_STATEFUL_DOMAINS=TU-DOMINIO.up.railway.app
```

Genera `APP_KEY` en local:

```bash
php artisan key:generate --show
```

#### Supabase Storage (justificantes / logos)

```env
SUPABASE_URL=https://xxxx.supabase.co
SUPABASE_ANON_KEY=...
SUPABASE_SERVICE_KEY=...
SUPABASE_BUCKET_JUSTIFICATIONS=justificantes-adjuntos
SUPABASE_BUCKET_INSTITUTION_LOGOS=institution-logos
```

#### PayPal (sandbox o live)

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
```

#### Correo (opcional en demo)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="GAMA Solutions"
```

Sin SMTP, deja `MAIL_MAILER=log` (no envía correos reales).

#### Tiempo real (Reverb) — ver Paso 6

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=TU-SERVICIO-REVERB.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https
```

### Paso 5 — Dominio público

1. Servicio web → **Settings** → **Networking** → **Generate Domain**.
2. Copia la URL (`https://xxxx.up.railway.app`).
3. Actualiza `APP_URL` y `SANCTUM_STATEFUL_DOMAINS` con ese host (sin `https://` en Sanctum: solo dominio).

### Paso 6 — Servicios adicionales (opcional pero recomendado)

Railway ejecuta **un proceso por servicio**. El `Procfile` define tres roles:

| Procfile | Qué hace | Cómo en Railway |
|----------|----------|-----------------|
| `web` | App HTTP | Servicio principal (por defecto) |
| `worker` | Colas `queue:work` | **+ New Service** → mismo repo → Start: `php artisan queue:work --sleep=3 --tries=3 --timeout=90` |
| `reverb` | WebSockets | **+ New Service** → Start: `php artisan reverb:start --host=0.0.0.0 --port=$PORT` |

**Reverb**

1. Duplica el servicio o crea uno nuevo desde el mismo repositorio.
2. **Settings** → **Deploy** → **Custom Start Command**:
   ```bash
   php artisan reverb:start --host=0.0.0.0 --port=$PORT
   ```
3. Genera dominio público para Reverb.
4. En el servicio **web**, apunta `REVERB_HOST` al host de Reverb y `REVERB_SCHEME=https`.

**Worker**

1. Nuevo servicio, mismo repo.
2. Start command:
   ```bash
   php artisan queue:work --sleep=3 --tries=3 --timeout=90
   ```
3. Copia las mismas variables de BD que el servicio web.

> Si no despliegas Reverb, usa `BROADCAST_CONNECTION=log` y la asistencia en tiempo real del docente usará solo **polling** (ya implementado).

### Paso 7 — Migraciones y seeders

`railway.json` incluye:

```json
"preDeployCommand": "php artisan migrate --force --no-interaction"
```

En cada deploy se aplican migraciones.

**Primera vez** (planes, roles): ejecuta en Railway **one-off shell** o local contra la BD de producción:

```bash
php artisan db:seed --class=PlansSeeder --force
php artisan db:seed --class=RolesSeeder --force
```

(Ajusta según los seeders que uses.)

### Paso 8 — Primer deploy

1. **Deploy** → espera build (composer + `npm run build`).
2. Revisa **Deploy Logs** — debe terminar con `php artisan serve` en el puerto `$PORT`.
3. Abre la URL pública → login.

### Paso 9 — Health check

Laravel expone `GET /up`. En Railway → **Settings** → Healthcheck path: `/up`.

---

## 4. Checklist post-despliegue

- [ ] `APP_KEY` definida
- [ ] `APP_DEBUG=false` en producción
- [ ] `APP_URL` coincide con el dominio Railway
- [ ] Migraciones OK (tablas en Postgres)
- [ ] Login web funciona (cookies / `SANCTUM_STATEFUL_DOMAINS`)
- [ ] Subida de justificantes (Supabase keys)
- [ ] PayPal callbacks apuntan a `APP_URL/paypal/success` y `/paypal/cancel`
- [ ] Worker activo si usas colas
- [ ] Reverb + variables si quieres tiempo real por WebSocket

---

## 5. Problemas frecuentes

| Síntoma | Solución |
|---------|----------|
| 500 al abrir la app | Revisa logs; falta `APP_KEY` o `DATABASE_URL` |
| 419 CSRF / sesión | `SESSION_DOMAIN` vacío; `SANCTUM_STATEFUL_DOMAINS` con tu dominio Railway |
| Assets sin estilo | Verifica que `npm run build` corrió en el build (Vite → `public/build`) |
| `config:cache` falla en build | Normal: el script optimiza en **runtime** (`railway-start.sh`) |
| Reverb no conecta | Servicio Reverb separado, `REVERB_HOST` correcto, `forceTLS` en front |
| Colas no procesan | Servicio **worker** desplegado y `QUEUE_CONNECTION=database` |

---

## 6. Costos y límites

- Plan Hobby: crédito mensual limitado; un servicio web + Postgres suele bastar para demo.
- Cada servicio extra (worker, reverb) consume más crédito.
- Para producción seria, valora plan Pro y BD administrada.

---

## 7. Comandos útiles (Railway CLI, opcional)

```bash
npm i -g @railway/cli
railway login
railway link
railway logs
railway run php artisan migrate:status
```

---

*G.A.M.A. Solutions — Control de Calidad / DevOps*
