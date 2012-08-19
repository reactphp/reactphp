<?php

// async DNS resolution

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$domain = 'igor.io';

$factory = new React\Dns\Resolver\Factory();
$resolver = $factory->create('8.8.8.8:53', $loop);
$resolver->resolve($domain, function ($ip) {
    echo "Host: $ip\n";
});

echo "Resolving domain $domain...\n";

$loop->run();
