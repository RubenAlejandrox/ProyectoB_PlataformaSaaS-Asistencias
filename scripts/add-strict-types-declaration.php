<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dirs = [
    'app/Http/Controllers',
    'app/Models',
    'app/Services',
    'app/Repositories',
    'app/Policies',
    'app/Jobs',
    'app/Http/Middleware',
    'app/Traits',
    'database/migrations',
    'database/seeders',
    'config',
];

$updated = 0;

foreach ($dirs as $dir) {
    $full = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $dir);
    if (! is_dir($full)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $content = file_get_contents($path);
        if ($content === false || str_contains($content, 'declare(strict_types=1)')) {
            continue;
        }
        if (! str_starts_with($content, '<?php')) {
            continue;
        }
        $new = preg_replace(
            '/(\*\/\s*\r?\n+)(namespace\s+)/',
            "$1declare(strict_types=1);\n\n$2",
            $content,
            1
        );
        if ($new === $content) {
            $new = preg_replace(
                '/(\*\/\s*\r?\n+)(use\s+)/',
                "$1declare(strict_types=1);\n\n$2",
                $content,
                1
            );
        }
        if ($new !== null && $new !== $content) {
            file_put_contents($path, $new);
            $updated++;
        }
    }
}

echo "declare(strict_types=1) añadido en {$updated} archivos.\n";
