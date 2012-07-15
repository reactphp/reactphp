# Http Component

Library for building an evented http server.

This component builds on top of the `Socket` component to implement HTTP. Here
are the main concepts:

* **Server**: Attaches itself to an instance of
  `React\Socket\ServerInterface`, parses any incoming data as HTTP, emits a
  `request` event for each request.
* **Request**: A `ReadableStream` which streams the request body and contains
  meta data which was parsed from the request header.
* **Response** A `WritableStream` which streams the response body. You can set
  the status code and response headers via the `writeHead()` method.

## Usage

This is an HTTP server which responds with `Hello World` to every request.

    $loop = React\EventLoop\Factory::create();
    $socket = new React\Socket\Server($loop);

    $http = new React\Http\Server($socket);
    $http->on('request', function ($request, $response) {
        $response->writeHead(200, array('Content-Type' => 'text/plain'));
        $response->end("Hello World!\n");
    });

    $socket->listen(1337);
    $loop->run();
