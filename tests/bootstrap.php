<?php

$autoload = require __DIR__ . '/../vendor/autoload.php';

// register all `autoload-dev` paths from React's components
foreach (glob(__DIR__ . '/../vendor/react/*/composer.json') as $b) {
    $config = json_decode(file_get_contents($b), true);

    if (isset($config['autoload-dev']['psr-4'])) {
        $base = dirname($b) . '/';
        foreach ($config['autoload-dev']['psr-4'] as $namespace => $paths) {
            foreach ((array)$paths as $path) {
                $autoload->addPsr4($namespace, $base . trim($path, '\\/'));
            }
        }
    }
}

// load all legacy test bootstrap scripts from React's components
foreach (glob(__DIR__ . '/../vendor/react/*/tests/bootstrap.php') as $b) {
    include $b;
}
