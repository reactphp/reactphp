# SocketServer

Stream-powered library for creating a socket server in PHP.

[![Build Status](https://secure.travis-ci.org/igorw/SocketServer.png)](http://travis-ci.org/igorw/SocketServer)

## Install

The recommended way to install SocketServer is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "igorw/socket-server": "dev-master"
    }
}
```

## Usage

### Events

Both `Igorw\Socket\Server` and `Igorw\Socket\Connection` extend
[événement](https://github.com/igorw/evenement), allowing you to bind to
events.

#### Server

* `connect`: Triggered whenever a new client connects to the server. Arguments: $conn.
* `input.*`: Triggered when custom input stream can be read. Arguments: $stream.
* `error`: Triggered when an error occurs. Arguments: $message.

#### Connection

* `data`: Triggered whenever a client sends data. Arguments: $data.
* `end`: Triggered whenever a client disconnects.
* `error`: Triggered when an error occurs. Arguments: $message, $conn.

### Input

In order to communicate with the server, you can add inputs via `addInput` and
bind to the `input.$name` event. This allows you to trigger custom events and
run custom code when they happen.

### Running

The `run` method will start the event loop. The server will process connections,
data and input until it dies.

### Example

Here is an example of a simple HTTP server listening on port 8000:
```php
<?php
use Igorw\Socket\Server;

$server = new Server('localhost', 8000);

$i = 1;

$server->on('connect', function ($conn) use (&$i) {
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

        $conn->write("HTTP 1.1 200 OK\r\n");
        $conn->write("Content-Type: text/html\r\n");
        $conn->write("Content-Length: $length\r\n");
        $conn->write("\r\n");
        $conn->write($response);
        $conn->close();
    });
});

$server->run();
```
## Tests

To run the test suite, you need PHPUnit.

    $ phpunit

## License

MIT, see LICENSE.
