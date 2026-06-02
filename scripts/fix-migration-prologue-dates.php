<?php

declare(strict_types=1);

$root = dirname(__DIR__).'/database/migrations';

foreach (glob($root.'/*.php') as $file) {
    $basename = basename($file);
    if (! preg_match('/^(\d{4})_(\d{2})_(\d{2})/', $basename, $m)) {
        continue;
    }
    $creado = "{$m[1]}-{$m[2]}-{$m[3]}";
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $updated = preg_replace(
        '/@creado\s+\d{4}-\d{2}-\d{2}/',
        "@creado       {$creado}",
        $content,
        1
    );
    if ($updated !== null && $updated !== $content) {
        file_put_contents($file, $updated);
        echo "OK {$basename} -> @creado {$creado}\n";
    }
}
