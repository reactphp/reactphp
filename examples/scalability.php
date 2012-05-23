<?php

// a simple, single-process, horizontal scalable http server listening on 10 ports

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

for ($i=0;$i<10;++$i) {
    $s=stream_socket_server('tcp://127.0.0.1:'.(8000+$i));
    $loop->addReadStream($s, function ($s) use ($i) {
        $c=stream_socket_accept($s);
        $len=strlen($i)+4;
        fwrite($c,"HTTP/1.1 200 OK\r\nContent-Length: $len\r\n\r\nHi:$i\n");
        echo "Served on port 800$i\n";
    });
}

echo "Access your brand new HTTP server on 127.0.0.1:800x. Replace x with any number from 0-9\n";

$loop->run();
