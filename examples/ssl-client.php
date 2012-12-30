<?php
 
    error_reporting(-1);
 
    $clientContext = stream_context_create(array("ssl" => array(
        'allow_self_signed' => true,
        'verify_peer' => false
    )));
 
    $connection = stream_socket_client('ssl://localhost:4096', $errcode, $errstr, 2, STREAM_CLIENT_CONNECT, $clientContext);
 
    if ($connection) {
        echo stream_get_contents($connection);
    } else {
        echo "\n\nError occured:\n$errcode\n$errstr\n";
    }
