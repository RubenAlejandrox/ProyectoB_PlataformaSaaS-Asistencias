# Estándar de documentación y código — GAMA (PHP / Blade)

## 4. Cabecera de prólogo (PHP)

Todo archivo fuente sujeto a revisión debe iniciar con el bloque de prólogo **inmediatamente después de** `<?php` y **antes del** `namespace`. La ausencia de cabecera es motivo de rechazo en code review.

## Archivos que requieren cabecera

| Categoría | Ubicación |
|-----------|-----------|
| Controllers | `app/Http/Controllers/` |
| Models | `app/Models/` |
| Services | `app/Services/` |
| Repositories | `app/Repositories/` |
| Contracts | `app/Repositories/Contracts/` |
| Policies | `app/Policies/` |
| Jobs | `app/Jobs/` |
| Middleware | `app/Http/Middleware/` |
| Migrations | `database/migrations/` |
| Seeders | `database/seeders/` |
| Helpers / Traits | `app/Helpers/`, `app/Http/Traits/`, `app/Traits/` |
| Config | `config/` |

## Exentos

`vendor/`, factories sin lógica propia, `.env`, vistas Blade (`.blade.php`).

## Plantilla

```php
<?php

/**
 * @descripcion  [1–2 líneas: propósito concreto del archivo]
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       YYYY-MM-DD
 * @modificado   YYYY-MM-DD
 *
 * @cambios      YYYY-MM-DD - Descripción del cambio
 */

declare(strict_types=1);           // 1° después del prólogo

namespace App\Services\X;          // 2° namespace
```

## Reglas de mantenimiento

| Campo | Regla |
|-------|--------|
| `@descripcion` | Obligatorio. Sin placeholders. |
| `@autor` | Creador original. **No se modifica** al editar. |
| `@autorizador` | Valida lógica y seguridad. |
| `@prueba` | Responsable de pruebas. |
| `@mantenimiento` | Optimización posterior. |
| `@version` | MAJOR.MINOR.PATCH; subir en cada cambio funcional. |
| `@creado` | Fecha de creación; **no cambiar** después. En migraciones: fecha del prefijo del archivo. |
| `@modificado` | Actualizar en cada cambio funcional. |
| `@cambios` | Una línea por cambio; **no borrar** historial. |

## Herramientas internas

```bash
# Prólogo + declare(strict_types=1) en archivos nuevos
php scripts/apply-prologue-headers.php

# Solo declare(strict_types=1) tras el prólogo
php scripts/add-strict-types-declaration.php

# @creado en migraciones según prefijo del archivo
php scripts/fix-migration-prologue-dates.php
```

---

## 6. Documentación de métodos (PHPDoc)

Cada **clase** y cada **método público** debe tener PHPDoc con `@param`, `@return` y `@throws` cuando aplique. Sin este bloque el PR se rechaza.

```php
/**
 * Calcula el monto total de una factura incluyendo impuestos.
 *
 * @param  Invoice $invoice  La factura a procesar.
 * @param  float   $taxRate  Tasa de impuesto (ej. 0.16).
 * @return float             Monto total con impuestos.
 * @throws InvalidArgumentException Si la tasa es negativa.
 */
public function calculateTotal(Invoice $invoice, float $taxRate): float
```

### 6.1 Comentarios en Blade

Prohibido `<!-- -->` para notas internas. Usar solo:

```blade
{{-- Comentario interno; no se envía al navegador --}}
```

Excepción: comentarios condicionales de clientes de correo (p. ej. `<!--[if mso]>` en plantillas email).

---

## 7. Orden obligatorio de declaraciones

1. `<?php`
2. Bloque de prólogo (`/** @descripcion ... */`)
3. `declare(strict_types=1);`
4. `namespace`
5. `use` (agrupados, ordenados)
6. `class` / `interface` / `trait`
7. Propiedades → constructor → **métodos públicos** → **métodos privados/protected al final**

---

*G.A.M.A. Solutions — Estándar de documentación v1.1*
