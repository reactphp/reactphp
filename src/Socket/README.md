# Socket Component

Library for building an evented socket server.

The socket component provides a more usable interface for a socket-layer
server or client based on the `EventLoop` and `Stream` components.

## Server

The server can listen on a port and will emit a `connection` event whenever a
client connects.

## Connection

The connection is a readable and writable stream. It can be used in a server
or in a client context.

## Usage

Here is a server that closes the connection if you send it anything.

    $loop = React\EventLoop\Factory::create();

    $socket = new React\Socket\Server($loop);
    $socket->on('connection', function ($conn) {
        $conn->write("Hello there!\n");
        $conn->write("Welcome to this amazing server!\n");
        $conn->write("Here's a tip: don't say anything.\n");

        $conn->on('data', function ($data) use ($conn) {
            $conn->close();
        });
    });
    $socket->listen(1337);

    $loop->run();
    
You can change the host the socket is listening on through a second parameter 
provided to the listen method:

    $socket->listen(1337, '192.168.0.1');

Here's a client that outputs the output of said server and then attempts to
send it a string.

    $loop = React\EventLoop\Factory::create();

    $client = stream_socket_client('tcp://127.0.0.1:1337');
    $conn = new React\Socket\Connection($client, $loop);
    $conn->pipe(new React\Stream\Stream(STDOUT, $loop));
    $conn->write("Hello World!\n");

    $loop->run();
