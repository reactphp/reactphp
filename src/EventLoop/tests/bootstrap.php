<?php

$loader = @include __DIR__ . '/../vendor/autoload.php';
if (!$loader) {
    $loader = require __DIR__ . '/../../../vendor/autoload.php';
}
$loader->addPsr4('React\\Tests\\EventLoop\\', __DIR__);
