<?php

namespace React\WebSocket;

use Evenement\EventEmitter;
use React\Http\ServerInterface as HttpServerInterface;
use React\Socket\ConnectionInterface;

/**
 * @event upgrade Emitted on successful websocket upgrade.
 * @event request Pass through Http request event.
 */
class Server extends EventEmitter implements ServerInterface
{
    private $io;
    private $http;

    public function __construct(HttpServerInterface $http)
    {
        $this->http = $http;
        $this->http->on('request', function($request, $response, $conn) {
			if (WebSocket::upgrade($request, $response)) {
				// XXX: I only really want to disconnect Http events, but doing so would require code changes to the HTTP code to cleanly disconnect only its events. For now, I'd rather deal with removing all listeners because I don't want to interfere with Http while Igor has major changes pending.
				$conn->removeAllListeners();

				$websocket = new WebSocket($conn, $request, $response);
				$websocket->on('data', function($data) {
					$this->emit('data', array($data));
				});
				$this->emit('upgrade', array($websocket));
			} else {
				$this->emit('request', array($request, $response));
			}
        });
    }
}
