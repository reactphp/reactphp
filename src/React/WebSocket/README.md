# WebSocket Component

Library for handling WebSockets on top of an evented http server.

This component piggybacks on top of the Http component, stepping in to handle
WebSockets where applicable.

## Usage

Usage is similar to Http, but with additional WebSocket upgrade and data events.
``` PHP
$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$http = new React\Http\Server($socket);
$wssrv = new React\WebSocket\Server($http);

// Attach request to the websocket, rather than to Http. Works the same.
$wssrv->on('request', function ($request, $response) {
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $response->end("Hello World!\n");
});

// Attach upgrade event to detect WebSocket upgrades.
$wssrv->on('upgrade', function($websocket) {
	$websocket->on('data', function($data) use ($websocket) {
		$websocket->write(strrev($data));
	});
});

$socket->listen(1337);
$loop->run();
```
