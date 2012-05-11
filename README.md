# React

Event-driven, non-blocking I/O with PHP.

[![Build Status](https://secure.travis-ci.org/react-php/react.png)](http://travis-ci.org/react-php/react)

## Install

The recommended way to install react is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "react/react": "dev-master"
    }
}
```

## Design goals

* Usable with a bare minimum of PHP extensions, add more extensions to get better performance.
* Provide a standalone event-loop component that can be re-used by other libraries.
* Decouple parts so they can be replaced by alternate implementations.

React is non-blocking by default. Use workers for blocking I/O.

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

## Community

Check out #reactphp on irc.freenode.net.

## Tests

To run the test suite, you need PHPUnit.

    $ phpunit

## License

MIT, see LICENSE.
