<?php

// echo server based on event loop
// includes buffer handling, but no pushback

require __DIR__.'/../vendor/autoload.php';

$server = stream_socket_server('tcp://0.0.0.0:5000');

$loop = new React\EventLoop\StreamSelectLoop();

$loop->onReadable($server, function ($server) use ($loop) {
    $conn = stream_socket_accept($server, 0);

    $buffer = '';
    $closing = false;

    $checkClose = function () use ($loop, &$conn, &$buffer, &$closing) {
        if ($closing && strlen($buffer) === 0) {
            $loop->remove($conn);
            fclose($conn);
        }
    };

    $loop->onReadable($conn, function ($conn) use ($loop, &$buffer, &$closing, $checkClose) {
        $data = fread($conn, 1024);

        if (!$data) {
            $closing = true;
            $loop->disableRead($conn);
            $checkClose();
            return;
        }

        $buffer .= $data;

        $loop->enableWrite($conn);
    });

    $loop->onWritable($conn, function ($conn) use ($loop, &$buffer, &$closing, $checkClose) {
        $written = fwrite($conn, $buffer);
        $buffer = (string) substr($buffer, $written);

        if (strlen($buffer) === 0) {
            $loop->disableWrite($conn);
        }

        $checkClose();
    });

    $loop->enableRead($conn);
});

$loop->enableRead($server);

$loop->run();
