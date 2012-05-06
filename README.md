# React

Nuclear Reactor written in PHP.

[![Build Status](https://secure.travis-ci.org/igorw/SocketServer.png)](http://travis-ci.org/igorw/SocketServer)

## Install

The recommended way to install SocketServer is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "react/react": "dev-master"
    }
}
```

## Usage

### Events

Both `React\Socket\Server` and `React\Socket\Connection` extend
[événement](https://github.com/igorw/evenement), allowing you to bind to
events.

### Example

Here is an example of a simple HTTP server listening on port 8000:
```php
<?php

$loop = new React\EventLoop\StreamSelectLoop();
$socket = new React\Socket\Server($loop);

$i = 1;

$socket->on('connect', function ($conn) use ($loop, &$i) {
    $conn->on('data', function ($data) use ($conn, &$i) {
        $lines = explode("\r\n", $data);
        $requestLine = reset($lines);

        if ('GET /favicon.ico HTTP/1.1' === $requestLine) {
            $response = '';
            $length = 0;
        } else {
            $response = "This is request number $i.\n";
            $length = strlen($response);
            $i++;
        }

        $conn->write("HTTP/1.1 200 OK\r\n");
        $conn->write("Content-Type: text/plain\r\n");
        $conn->write("Content-Length: $length\r\n");
        $conn->write("Connection: close\r\n");
        $conn->write("\r\n");
        $conn->write($response);
        $conn->end();
    });
});

$socket->listen(8000);
$loop->run();
```
## Tests

To run the test suite, you need PHPUnit.

    $ phpunit

## License

MIT, see LICENSE.
