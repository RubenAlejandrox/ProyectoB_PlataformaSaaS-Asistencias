<?php

/**
 * Aplica cabeceras de prólogo (uso único / mantenimiento). No forma parte del despliegue.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$team = [
    'autor'         => 'Rubén Alejandro Nolasco Ruiz',
    'autorizador'   => 'Rubén Alejandro Nolasco Ruiz',
    'prueba'        => 'Diego Miguel Hernandez Fabela',
    'mantenimiento' => 'Ghael Garcia Manjarrez',
];

$scanDirs = [
    'app/Http/Controllers',
    'app/Models',
    'app/Services',
    'app/Repositories',
    'app/Repositories/Contracts',
    'app/Policies',
    'app/Jobs',
    'app/Http/Middleware',
    'app/Helpers',
    'app/Http/Traits',
    'app/Traits',
    'database/migrations',
    'database/seeders',
    'config',
];

$overrides = [
    'AttendanceWebController.php' => [
        'descripcion' => 'Controlador web de asistencias (docente/alumno): sesiones, claves, registro y roster en tiempo real.',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Optimización roster/polling y marcado masivo de faltas\n *               2026-06-02 - Incorporación de cabecera de prólogo",
    ],
    'AttendanceProgressService.php' => [
        'descripcion' => 'Cálculo de porcentaje de asistencia, semáforo y proyección; incluye cálculo bulk para roster.',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Método calculateBulk para evitar N+1 en roster\n *               2026-06-02 - Incorporación de cabecera de prólogo",
    ],
    'AttendanceRegistered.php' => [
        'descripcion' => 'Evento broadcast de asistencia registrada (Reverb) con estado y progreso del alumno.',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Payload con pct y light para actualización en tiempo real\n *               2026-06-02 - Incorporación de cabecera de prólogo",
    ],
    'SubscriptionService.php' => [
        'descripcion' => 'Reglas de negocio de suscripciones: una activa por institución, asignación y upgrade Free→PRO.',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Incorporación de cabecera de prólogo",
    ],
    'AuthController.php' => [
        'descripcion' => 'Autenticación, bloqueo por intentos y recuperación de contraseña sin correo (contacto admin).',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Flujo forgot-password y búsqueda de administrador\n *               2026-06-02 - Incorporación de cabecera de prólogo",
    ],
    'AdminEditController.php' => [
        'descripcion' => 'Edición administrativa transaccional y restablecimiento de contraseñas globales.',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Reset de contraseña sin restricción por institución\n *               2026-06-02 - Incorporación de cabecera de prólogo",
    ],
    'ClassroomController.php' => [
        'descripcion' => 'CRUD de aulas, campo grupo (6 dígitos) y exportación de alumnos inscritos.',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Campo grupo y validación de unicidad\n *               2026-06-02 - Incorporación de cabecera de prólogo",
    ],
    'Classroom.php' => [
        'descripcion' => 'Modelo de aula/materia con grupo, periodo y umbral mínimo de asistencia.',
        'version'     => '1.1.0',
        'cambios'     => "2026-06-02 - Atributo grupo y displayName()\n *               2026-06-02 - Incorporación de cabecera de prólogo",
    ],
];

function describeFile(string $basename, string $relativePath): array
{
    global $overrides;

    if (isset($overrides[$basename])) {
        return $overrides[$basename];
    }

    if (str_contains($relativePath, 'database/migrations')) {
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $basename, 1) ?? $basename;
        $name = str_replace('.php', '', $name);

        return [
            'descripcion' => "Migración de esquema: {$name}.",
            'version'     => '1.0.0',
            'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
        ];
    }

    if (str_contains($relativePath, 'database/seeders')) {
        $name = str_replace(['Seeder.php', '.php'], '', $basename);

        return [
            'descripcion' => "Seeder de datos iniciales: {$name}.",
            'version'     => '1.0.0',
            'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
        ];
    }

    if (str_contains($relativePath, 'config/')) {
        $name = str_replace('.php', '', $basename);

        return [
            'descripcion' => "Archivo de configuración Laravel: {$name}.",
            'version'     => '1.0.0',
            'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
        ];
    }

    if (str_ends_with($basename, 'Controller.php')) {
        $short = str_replace('Controller.php', '', $basename);

        return [
            'descripcion' => "Controlador HTTP del módulo {$short}: expone acciones web/API del dominio.",
            'version'     => '1.0.0',
            'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
        ];
    }

    if (str_ends_with($basename, 'Service.php')) {
        $short = str_replace('Service.php', '', $basename);

        return [
            'descripcion' => "Servicio de dominio {$short}: encapsula reglas de negocio reutilizables.",
            'version'     => '1.0.0',
            'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
        ];
    }

    if (str_contains($relativePath, 'Middleware')) {
        $short = str_replace('.php', '', $basename);

        return [
            'descripcion' => "Middleware HTTP {$short}: filtra o enriquece solicitudes entrantes.",
            'version'     => '1.0.0',
            'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
        ];
    }

    if (str_contains($relativePath, 'Traits')) {
        $short = str_replace('.php', '', $basename);

        return [
            'descripcion' => "Trait reutilizable {$short} para modelos o controladores.",
            'version'     => '1.0.0',
            'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
        ];
    }

    $short = str_replace('.php', '', $basename);

    return [
        'descripcion' => "Modelo Eloquent {$short}: representa entidad y relaciones del dominio.",
        'version'     => '1.0.0',
        'cambios'     => '2026-06-02 - Incorporación de cabecera de prólogo conforme estándar',
    ];
}

function buildPrologue(array $meta, array $team): string
{
    $desc = $meta['descripcion'];
    $cambios = $meta['cambios'];
    $cambiosLines = str_contains($cambios, "\n")
        ? $cambios
        : " *               {$cambios}";

    return <<<PROLOGUE
/**
 * @descripcion  {$desc}
 *
 * @autor          {$team['autor']}
 * @autorizador    {$team['autorizador']}
 * @prueba         {$team['prueba']}
 * @mantenimiento  {$team['mantenimiento']}
 *
 * @version      {$meta['version']}
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios      {$cambiosLines}
 */


PROLOGUE;
}

function applyPrologue(string $filePath, string $relativePath, array $team): bool
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    if (str_contains($content, '@descripcion')) {
        return false;
    }

    if (! str_starts_with($content, '<?php')) {
        return false;
    }

    $basename = basename($filePath);
    $meta     = describeFile($basename, $relativePath);
    $prologue = buildPrologue($meta, $team);

    $rest = preg_replace('/^<\?php\r?\n/', '', $content, 1);
    if ($rest === null) {
        return false;
    }

    // Quitar declare(strict_types) existente para no duplicar (proyecto sin strict_types previo)
    $rest = preg_replace('/^declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*\r?\n/', '', $rest, 1) ?? $rest;

    $rest = preg_replace('/^declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*\r?\n/', '', $rest, 1) ?? $rest;

    $newContent = "<?php\n\n{$prologue}declare(strict_types=1);\n\n{$rest}";
    file_put_contents($filePath, $newContent);

    return true;
}

$updated = 0;
$skipped = 0;

foreach ($scanDirs as $dir) {
    $fullDir = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $dir);
    if (! is_dir($fullDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        $rel  = str_replace('\\', '/', substr($path, strlen($root) + 1));

        if (applyPrologue($path, $rel, $team)) {
            $updated++;
            echo "OK  {$rel}\n";
        } else {
            $skipped++;
        }
    }
}

echo "\nActualizados: {$updated}, omitidos: {$skipped}\n";
