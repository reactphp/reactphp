<?php

// downloading the two best technologies ever in parallel

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$domain = 'igor.io';

$resolver = new React\Dns\Resolver('8.8.8.8', $loop);
$resolver->resolve($domain, function ($ip) {
    echo "Host: $ip\n";
});

echo "Resolving domain $domain...\n";

$loop->run();
