# Proyecto B — Plataforma SaaS de Gestión de Asistencias y Cumplimiento Académico

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/PostgreSQL-16-316192?style=for-the-badge&logo=postgresql&logoColor=white"/>
  <img src="https://img.shields.io/badge/Supabase-Storage-3ECF8E?style=for-the-badge&logo=supabase&logoColor=white"/>
  <img src="https://img.shields.io/badge/PayPal-Sandbox-00457C?style=for-the-badge&logo=paypal&logoColor=white"/>
  <img src="https://img.shields.io/badge/Tests-54%20passed-28A745?style=for-the-badge"/>
</p>

---

## Descripción General

**GAMA Asistencias** es una plataforma SaaS multi-tenant para la gestión digital de asistencias académicas. Permite a instituciones educativas administrar docentes, alumnos, sesiones, justificantes y reportes bajo un modelo de suscripción por plan.

El sistema está compuesto por:

- **Web (Laravel)** — Panel administrativo y portal docente/alumno
- **API REST (Sanctum)** — Consumida por la App Móvil Flutter
- **Tiempo real (Reverb)** — Actualizaciones instantáneas de asistencia y semáforo
- **Almacenamiento (Supabase Storage)** — Logos institucionales y justificantes

---

## Equipo Responsable

> ⚠️ **Solo el Líder de Desarrollo y el Desarrollador UX/UI tienen permitido hacer cambios en este repositorio. Queda prohibido que cualquier responsable no mencionado realice modificaciones.**

| Rol | Nombre |
|-----|--------|
| Líder de Desarrollo Web | Rubén Alejandro Nolasco Ruiz |
| Desarrollador UX/UI | Diego Miguel Hernández Fabela |
| QA / Pruebas | Diego Miguel Hernández Fabela |
| Mantenimiento | Ghael Garcia Manjarrez |

---

## Stack Tecnológico

### Backend
| Tecnología | Versión | Uso |
|-----------|---------|-----|
| Laravel | 12.x | Framework principal (API + Web) |
| PHP | 8.3 | Lenguaje backend |
| PostgreSQL | 16 | Base de datos principal |
| Supabase | Cloud | BD + Storage (producción y testing) |
| Laravel Sanctum | — | Autenticación API con Bearer Token |
| Laravel Reverb | — | WebSocket / Tiempo real |
| Spatie Permission | — | RBAC (roles y permisos) |
| Maatwebsite/Excel | — | Exportación XLSX |
| PayPal SDK | — | Pagos sandbox y producción |
| Composer/CA-Bundle | — | Fix SSL Windows para PayPal |

### Frontend
| Tecnología | Uso |
|-----------|-----|
| Blade | Motor de plantillas web |
| Alpine.js | Interactividad ligera (countdown, modales) |
| Tailwind / CSS propio | Estilos basados en Design System GAMA |
| Font Awesome | Íconos |
| Vite | Empaquetador de assets |

### Infraestructura
| Servicio | Uso |
|---------|-----|
| Supabase (`gama-asistencias-saas`) | Base de datos producción |
| Supabase (`gama-asistencias-testing`) | Base de datos tests |
| Supabase Storage | Bucket `institution-logos`, `justification-files` |
| PayPal Sandbox | Pruebas de pago |

---

## Design System GAMA

### Paleta de Colores

| Nombre | Código | Uso |
|--------|--------|-----|
| Deep Corporate Blue | `#134474` | Sidebar, Navbar, botones principales, encabezados |
| GAMA Orange | `#F28B2C` | Alertas, KPIs, indicadores de acción |
| Ice Blue | `#F2F7FB` | Fondo de cards y tablas |
| Success Green | `#28A745` | Confirmaciones, estados activos |
| Error Red | `#DC3545` | Validaciones y acciones críticas |
| Warning Amber | `#FFC107` | Advertencias, semáforo ámbar |

### Tipografía

| Elemento | Tamaño | Peso | Uso |
|----------|--------|------|-----|
| H1 | 32px | Bold | Encabezados de página |
| H2 | 24px | SemiBold | Títulos de sección |
| Body | 16px | Regular | Texto general |
| Labels | 16px | Medium | Formularios |
| Code | 14px | Mono | Códigos de acceso y claves |

---

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                    GAMA Asistencias                     │
├─────────────────┬───────────────────┬───────────────────┤
│   Web (Blade)   │   API (Sanctum)   │  Tiempo Real      │
│   Admin Panel   │   Flutter App     │  Reverb/WS        │
├─────────────────┴───────────────────┴───────────────────┤
│              Laravel 12 + PHP 8.3                       │
├─────────────────────────────────────────────────────────┤
│         PostgreSQL 16 — Supabase Cloud                  │
├──────────────────────┬──────────────────────────────────┤
│  Supabase Storage    │  PayPal Sandbox / Live           │
└──────────────────────┴──────────────────────────────────┘
```

### Roles del Sistema

| Rol | Capacidades |
|-----|-------------|
| `Administrator` | Gestión de instituciones, membresías, edición administrativa, auditoría |
| `Teacher` | Gestión de aulas, sesiones, claves, justificantes, reportes, cierre de ciclo |
| `Student` | Registro de asistencia, progreso, justificantes |

---

## Módulos Implementados

### Módulo 1 — Autenticación y Seguridad
- Login con Bcrypt + bloqueo tras 3 intentos fallidos (`locked_until`)
- Registro por rol:
  - **Docente**: requiere código de institución de 8 caracteres (vigencia 7 días)
  - **Alumno**: código de aula opcional (vigencia 48h, reutilizable)
- SoftDeletes en usuarios
- `SetInstitutionScope` middleware para aislamiento multi-tenant
- `CheckRole`, `CheckPlanAccess`, `LogAuditoria` middlewares

### Módulo 2 — Instituciones y Membresías
- CRUD completo de instituciones con logo en Supabase Storage
- Generación de códigos de institución para onboarding de docentes
- Planes: Free (15 alumnos) / Pro ($199 MXN, 50 alumnos)
- Suscripciones con PayPal Sandbox — flujo: `createOrder → approve → captureOrder`
- `processRenewal()` con 3 reintentos y suspensión automática tras fallos
- Tabla `payments` con `paypal_order_id` y `paypal_capture_id`

### Módulo 3 — Gestión de Aulas
- CRUD de aulas con límite por plan (`lockForUpdate` para race conditions)
- Códigos de invitación de 8 caracteres — reutilizables, expiración por tiempo
- Regeneración de código invalida anteriores expirándolos (`expires_at = now()`)
- Vista con tarjetas y tabla, countdown `dd:hh:mm`, modal de expiración automática

### Módulo 4 — Control de Asistencias
- `SessionController`: abrir/cerrar sesión por aula
- `SessionKeyController`: clave alfanumérica de 8 chars, duración 15/30/60 min
- `AttendanceController`: validación de clave → inscripción → unicidad → registro
- `AttendanceProgressService`: `P = (present + approved) / total × 100`
- Semáforo: `green` ≥ umbral | `amber` ≥ umbral−10 | `red` < umbral−10
- Al cerrar sesión: faltas automáticas a alumnos no registrados

### Módulo 5 — Inscripciones
- `EnrollmentService`: centraliza inscripción, valida código, respeta capacidad máxima
- Crea o reactiva `enrollment` existente
- Alineación de `institution_id` del alumno al inscribirse

### Módulo 6 — Justificantes
- Ventana de 72 horas desde `attendances.created_at`
- Solo faltas con `status = absent` son justificables
- Subida de archivos PDF/JPG/PNG a bucket `justification-files`
- Dictamen docente: aprobado/rechazado + recálculo automático de semáforo
- `TrafficLightAlert` al cambiar estado tras aprobación

### Módulo 7 — Tiempo Real (Reverb)
- `AttendanceRegistered` → canal `private-attendance.{classroomId}`
- `TrafficLightAlert` → canal `private-progress.{studentId}`
- Vista docente: lista de asistencia en tiempo real con Echo/Alpine.js
- Vista alumno: semáforo y barra de progreso actualizados en vivo

### Módulo 8 — Reportes
- `ReportGeneratorService`: matriz A/F/J y resumen mensual
- Exportación XLSX con Maatwebsite/Excel
- Envío por correo con `AttendanceReportMail` + archivo adjunto
- Preview de alumnos en riesgo (semáforo rojo/ámbar)

### Módulo 9 — Cierre de Ciclo
- Verificación de prerequisitos: sin justificantes pendientes, sesiones cerradas
- `closure_key_hash` — clave de seguridad hasheada
- Máximo 3 intentos, bloqueo de 24 horas al tercer fallo
- Archivo del ciclo y desactivación del aula

### Módulo 10 — Edición Administrativa
- `AdminEditController`: corrección de asistencia, baja de alumno, eliminación de sesión
- Toda operación en transacción DB — si falla el log, se revierte el cambio
- Auditoría completa en tabla `audit_log` (INSERT-only)

---

## Estructura de Base de Datos

### Tablas principales (18 tablas)

| Tabla | Descripción |
|-------|-------------|
| `plans` | Planes Free y Pro con límites |
| `institutions` | Tenant raíz del sistema |
| `subscriptions` | Suscripciones activas por institución |
| `payments` | Historial de pagos PayPal |
| `users` | Usuarios con roles, softDeletes, bloqueo |
| `institution_codes` | Códigos para onboarding de docentes (8 chars, 7 días) |
| `classrooms` | Aulas por institución y docente |
| `enrollments` | Relación alumno-aula con estado activo/inactivo |
| `invitation_codes` | Códigos de aula para alumnos (48h, reutilizable) |
| `class_sessions` | Sesiones de clase (renombrada de `sessions`) |
| `session_keys` | Claves temporales por sesión (FK explícita a `class_sessions`) |
| `attendances` | Registros de asistencia (FK explícita a `class_sessions`) |
| `justifications` | Justificantes con archivo y dictamen |
| `academic_cycles` | Ciclos con `closure_key_hash` y bloqueo |
| `audit_log` | Auditoría de todas las modificaciones manuales |
| `alerts` | Alertas de semáforo por alumno y aula |
| `model_has_roles` | Spatie RBAC con UUID fix |
| `model_has_permissions` | Spatie RBAC con UUID fix |

> **Decisión importante**: La tabla del dominio se llama `class_sessions` para no colisionar con la tabla `sessions` de Laravel. El modelo usa `protected $table = 'class_sessions'`.

---

## Instalación

### Requisitos previos
- PHP 8.3+
- Composer
- Node.js 18+
- Cuenta Supabase (2 proyectos: producción y testing)
- Cuenta PayPal Developer (Sandbox)

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/tu-org/ProyectoB_PlataformaSaaS-Asistencias.git
cd ProyectoB_PlataformaSaaS-Asistencias

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias JS
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar variables de entorno (ver sección Variables de Entorno)

# 6. Ejecutar migraciones
php artisan migrate

# 7. Compilar assets
npm run dev

# 8. Levantar servidor
php artisan serve
```

---

## Variables de Entorno

```env
# Aplicación
APP_NAME="GAMA Asistencias"
APP_URL=http://127.0.0.1:8000
APP_ENV=local

# Sesión — CRÍTICO: usar file para no colisionar con tabla sessions del dominio
SESSION_DRIVER=file
SESSION_DOMAIN=

# Base de datos producción
DB_CONNECTION=pgsql
DB_HOST=db.wpgzcsjyurrfyjyaxfvb.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=tu_password

# Supabase
SUPABASE_URL=url
SUPABASE_ANON_KEY=tu_anon_key
SUPABASE_SERVICE_KEY=tu_service_key

# PayPal
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=tu_client_id
PAYPAL_SECRET=tu_secret
PAYPAL_CURRENCY=MXN
PAYPAL_LOCALE=es-MX

# Reverb (WebSocket)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=tu_app_id
REVERB_APP_KEY=tu_app_key
REVERB_APP_SECRET=tu_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,localhost:8000,127.0.0.1:8000
```

### Base de datos de testing (`phpunit.xml`)
```xml
<env name="DB_HOST" value="db.fdxializioqqejwwyhnd.supabase.co"/>
<env name="PAYPAL_MODE" value="sandbox"/>
<env name="PAYPAL_CLIENT_ID" value="tu_client_id"/>
<env name="PAYPAL_SECRET" value="tu_secret"/>
<env name="PAYPAL_CURRENCY" value="MXN"/>
```

---

## Levantar el Sistema Completo

Requiere **3 terminales** en paralelo:

```bash
# Terminal 1 — Servidor Laravel
php artisan serve

# Terminal 2 — WebSocket Reverb
php artisan reverb:start --debug

# Terminal 3 — Queue Worker (procesa eventos broadcast)
php artisan queue:work

# Terminal 4 (opcional) — Vite para assets en desarrollo
npm run dev
```

> **Windows**: Si el puerto 8080 está ocupado, ejecutar `netstat -ano | findstr :8080` y `taskkill /PID <pid> /F`, luego reiniciar Reverb.

---

## Tests

### Ejecutar todos los tests

```bash
php artisan test
```

### Resultado esperado: **54 tests, 81+ assertions**

| Suite | Tests | Estado |
|-------|-------|--------|
| `AuthBcryptTest` | 7 | ✅ |
| `MiddlewareTest` | 12 | ✅ |
| `PayPalServiceTest` | 6 | ✅ |
| `EnrollmentTest` | 4 | ✅ |
| `AttendanceTest` | 7 | ✅ |
| `ProgressServiceTest` | 3 | ✅ |
| `JustificationTest` | 4 | ✅ |
| `ReportGeneratorServiceTest` | 3 | ✅ |
| `CycleClosureTest` | 4 | ✅ |
| `AuditLogTest` | 4 | ✅ |

### Ejecutar una suite específica

```bash
php artisan test tests/Unit/PayPalServiceTest.php
php artisan test tests/Feature/MiddlewareTest.php
php artisan test --filter "create_order_returns_order_id"
```

---

## Credenciales de Prueba

| Rol | Email | Password |
|-----|-------|----------|
| Administrator | admin@gama.com | Admin1234$ |
| Teacher | docente@gama.com | (registrado con institution_code) |
| Student | alumno@gama.com | Alumno1234$ |
| PayPal Sandbox | Cuenta Personal en developer.paypal.com | — |

---

## Flujos Principales

### Flujo de Registro por Rol

```
Administrador → seeder o panel
Docente → registro con institution_code (generado por Admin en /instituciones)
Alumno → registro con invitation_code de aula (opcional, cae a GAMA Demo si no)
```

### Flujo de Pago PayPal

```
Admin selecciona plan → upgrade() → createOrder() → redirect PayPal sandbox
→ paypalSuccess() → captureOrder() → crea subscription + payment
```

### Flujo de Asistencia

```
Docente abre sesión → genera session_key (15/30/60 min)
→ Alumno ingresa clave → AttendanceController::register()
→ AttendanceRegistered (Reverb) → recálculo semáforo
→ Docente cierra sesión → faltas automáticas a no registrados
```

### Flujo de Justificante

```
Alumno sube justificante (ventana 72h desde falta)
→ Docente dictamina → si aprobado: recálculo P + TrafficLightAlert
```

### Flujo de Cierre de Ciclo

```
Verificar sin pending justificantes → ingresar closure_key
→ 3 intentos máx (bloqueo 24h) → cerrar ciclo + desactivar aula
```

---

## Vistas Implementadas (PANT-01 a PANT-14)

| Pantalla | Ruta | Descripción |
|----------|------|-------------|
| PANT-01 | `/login` | Login + registro con tabs, código dinámico por rol |
| PANT-02 | `/dashboard` (Admin) | KPIs, plan activo, alertas, estadísticas |
| PANT-03 | `/dashboard` (Teacher) | Mis aulas, accesos rápidos |
| PANT-04 | `/dashboard` (Student) | Progreso por materia, semáforo |
| PANT-05 | `/instituciones` | CRUD instituciones + generar código + logo |
| PANT-06 | `/membresias` | Tabla suscripciones + modales PayPal |
| PANT-07 | `/aulas` | Tarjetas + tabla, countdown dd:hh:mm, modal expiración |
| PANT-08 | `/aulas/create` | Formulario creación de aula |
| PANT-09 | `/asistencias/docente` | Countdown mm:ss, regenerar clave, alumnos en tiempo real |
| PANT-10 | `/asistencias/alumno` | Input clave, barras progreso semáforo, justificante |
| PANT-11 | `/reportes` | Selector aula/mes, descarga XLSX, envío correo |
| PANT-12 | `/ciclo/cierre` | Checklist real, intentos restantes, form clave |
| PANT-13 | `/justificantes` | KPIs, pestañas, filtro por rol, dictamen |
| PANT-14 | `/admin/edicion` | Corrección/baja/eliminar + auditoría reciente |

---

## Estructura de Archivos Clave

```
app/
├── Events/
│   ├── AttendanceRegistered.php
│   └── TrafficLightAlert.php
├── Exports/
│   ├── AttendanceMatrixExport.php
│   └── MonthlySummaryExport.php
├── Http/
│   ├── Controllers/
│   │   ├── AdminEditController.php
│   │   ├── AttendanceController.php
│   │   ├── AttendanceWebController.php
│   │   ├── AuthController.php
│   │   ├── ClassroomController.php
│   │   ├── CycleController.php
│   │   ├── DashboardController.php
│   │   ├── EnrollmentController.php
│   │   ├── InstitutionController.php
│   │   ├── InvitationCodeController.php
│   │   ├── JustificationController.php
│   │   ├── PaymentController.php
│   │   ├── ReportController.php
│   │   ├── SessionController.php
│   │   ├── SessionKeyController.php
│   │   └── SubscriptionController.php
│   └── Middleware/
│       ├── CheckPlanAccess.php
│       ├── CheckRole.php
│       ├── LogAuditoria.php
│       └── SetInstitutionScope.php
├── Mail/
│   └── AttendanceReportMail.php
├── Models/
│   ├── AcademicCycle.php
│   ├── Alert.php
│   ├── AuditLog.php
│   ├── Attendance.php
│   ├── Classroom.php
│   ├── Enrollment.php
│   ├── Institution.php
│   ├── InstitutionCode.php
│   ├── InvitationCode.php
│   ├── Justification.php
│   ├── Payment.php
│   ├── Plan.php
│   ├── Session.php           ← protected $table = 'class_sessions'
│   ├── SessionKey.php
│   ├── Subscription.php
│   └── User.php
├── Services/
│   ├── AttendanceProgressService.php
│   ├── EnrollmentService.php
│   ├── PayPalService.php
│   ├── PayPalSdkClient.php   ← Fix SSL Windows
│   ├── ReportGeneratorService.php
│   └── SupabaseStorageService.php
└── Traits/
    ├── HasInstitutionScope.php
    └── HasUuidKey.php
```

---

## Decisiones Técnicas Importantes

| Decisión | Razón |
|----------|-------|
| `sessions` → `class_sessions` | Evitar colisión con tabla `sessions` de Laravel |
| `SESSION_DRIVER=file` | Evitar conflicto con tabla `sessions` del dominio en Supabase |
| `HasUuidKey` trait | UUID generado en PHP antes del INSERT para compatibilidad Supabase |
| `HasInstitutionScope` NO en `Institution` | El scope usa `$query->getModel()->getTable()` para evitar recursión |
| `is_used` no se marca al inscribir | Códigos de aula son reutilizables; se invalidan expirándolos |
| `PayPalSdkClient` subclase | Fix SSL en Windows con `composer/ca-bundle` |
| `PAYPAL_LOCALE=es-MX` (guión, no guión bajo) | PayPal Orders v2 requiere formato BCP 47 |
| `lockForUpdate()` en inscripción y aulas | Previene race conditions en último cupo |
| Transacción obligatoria en `AdminEditController` | Si falla el log, se revierte el cambio |
| `auth()->user()->id` en lugar de `auth()->id()` | UUID no se infiere correctamente con `auth()->id()` |

---

## Integración con App Móvil Flutter

La API REST está documentada en:
`GAMA_GuiaIntegracionFlutterLaravel_v1.docx`

Endpoints base: `https://tu-dominio.com/api`

Canales Reverb:
- `private-attendance.{classroomId}` → evento `.attendance.registered`
- `private-progress.{studentId}` → evento `.traffic.light`

---

## Pendiente (Fases 6 y 7)

### Fase 6 — QA y Seguridad
- [ ] Suite Pest con cobertura ≥ 80%
- [ ] OWASP Top 10 (CSRF, XSS, SQL Injection, RLS)
- [ ] Análisis N+1 con Debugbar
- [ ] Prueba de carga 200 usuarios simultáneos
- [ ] Endurecimiento: validaciones edge cases + mensajes UX homogéneos

### Fase 7 — Despliegue
- [ ] Nginx + PHP-FPM + SSL
- [ ] Supabase producción
- [ ] PayPal Live
- [ ] GitHub Actions CI/CD
- [ ] Acta de entrega + tag `v1.0.0`

---

## Licencia y Propiedad Intelectual

© 2026 **GAMA Solutions S.A. de C.V.** — *"El factor de cambio en tu tecnología"*

Todos los derechos reservados. Este software es propiedad exclusiva de GAMA Solutions S.A. de C.V. Queda prohibida su reproducción, distribución o modificación sin autorización expresa por escrito del titular.

---

*Versión: 1.0.0 — Mayo 2026 — GAMA Solutions S.A. de C.V.*
