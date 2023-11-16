<?php

$autoload = require __DIR__ . '/../vendor/autoload.php';

assert($autoload instanceof Composer\Autoload\ClassLoader);

// register all `autoload-dev` paths from ReactPHP's components
foreach (glob(__DIR__ . '/../vendor/react/*/composer.json') as $b) {
    $config = json_decode(file_get_contents($b), true);

    if (isset($config['autoload-dev']['psr-4'])) {
        $base = dirname($b) . '/';
        foreach ($config['autoload-dev']['psr-4'] as $namespace => $paths) {
            foreach ((array)$paths as $path) {
                $autoload->addPsr4($namespace, $base . $path);
            }
        }
    }
}
