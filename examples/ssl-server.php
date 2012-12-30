<?php

require dirname(__DIR__).'/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop, 
    array('ssl' => array(
        'local_cert' => __DIR__.'/certificate.pem',
    ))
);

$socket->on('connection', function ($conn) {
    $conn->write("This message is secure!\n");
});

echo "Socket server listening on port 4096.\n";
echo "Run ssl-client.php to receive a secure message\n";

$socket->listen(4096);
$loop->run();
