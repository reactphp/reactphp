<?php
/**
 * This file defines the WebSocket class
 */
namespace React\WebSocket;

use Evenement\EventEmitter;
use React\Http\Request as HttpRequest;
use React\Http\Response as HttpResponse;
use React\Socket\ConnectionInterface;
/**
 * Common data and functions for web sockets.
 */
class WebSocket extends EventEmitter
{
    /**
     * Supported websocket protocol version.
     */
    const VERSION = 13;

    /**
     * UUID used sort of like a "shared secret" for websockets.
     */
    const UUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

    /**
     * Determine if a given request is an HTTP to WebSocket upgrade request.
     *
     * @return bool True if this is a WebSocket upgrade request.
     */
    public static function isUpgrade(HttpRequest $request)
    {
        $headers = $request->getHeaders();

        if ($request->getHttpVersion() != "1.1") {
            return FALSE;
        } elseif (strcasecmp($headers['Connection'], "upgrade") !== 0) {
            return FALSE;
        } elseif (strcasecmp($headers['Upgrade'], "websocket") !== 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Populate a response with the headers necessary for a websocket upgrade.
     *
     * @param HttpRequest $request
     * @param HttpResponse &$response
     */
    public static function upgrade(HttpRequest $request, HttpResponse $response)
    {
        /* If this is *not* a WebSocket upgrade, leave it be. */
        if (!static::isUpgrade($request)) {
            return FALSE;
        }

        $headers = $request->getHeaders();

        /* Let the client know if we/they are using an unsupported version */
        if ($headers['Sec-WebSocket-Version'] != self::VERSION) {
            $response->writeHead(426, [
                'Sec-WebSocket-Version' => self::VERSION
            ]);
            return FALSE;
        }

        /* WebSocket key is a 16-byte key required as part of the response to prove we're websocket-capable. */
        if (empty($headers['Sec-WebSocket-Key'])) { /* A protocol oddity is that we're not required to decode it, so don't bother decoding it. */
            $response->writeHead(400, []);
            return FALSE;
        }

        $response->writeHead(101, [
            'Connection' => 'upgrade',
            'Upgrade' => 'websocket',
            'Sec-WebSocket-Accept' => base64_encode(hash('sha1', $headers['Sec-WebSocket-Key'] . self::UUID, TRUE))
        ]);

        return TRUE;
    }

    /**
     * A buffer to store the data as it is read, and its length.
     *
      * @var string
     * @var int
     */
    private $buffer = "";
    private $buflen = 0;

    private $conn;
    private $request;
    private $response;

    public function __construct(ConnectionInterface $conn, HttpRequest $request, HttpResponse $response)
    {
        $this->conn = $conn;
        $this->request = $request;
        $this->response = $response;

        $conn->on('data', array($this, 'feed'));

		$websocket = $this;
        $conn->on('end', function () use ($websocket, $request) {
            $websocket->emit('end');
            $request->emit('end');
        });
        $this->on('pause', function () use ($conn) {
            $conn->emit('pause');
        });
        $this->on('resume', function () use ($conn) {
            $conn->emit('resume');
        }); 
    }

    /**
     * Event handler for data, feeding data into internal buffers for WebSocket frame parsing.
     *
     * @param string $data
     */
    protected function feed($data) {
        $this->buffer .= $data;
        $this->buflen += strlen($data);

        if ($frame = Frame::unpack($this->buffer, $this->buflen)) {
            $this->emit('data', array($frame->getPayload()));
        }
    }

    /**
     * Write data through the websocket.
     *
     * Note: there is currently no mechanism to control the frame type or frame sizes.
     *
     * @param string $data Data to write.
     */
    public function write($data)
    {
        if (empty($data)) {
            return;
        }

        $frame = new Frame(TRUE, Frame::OPCODE_TEXT, FALSE, strlen($data), $data);
        list($buf, $buflen) = $frame->pack();

        $this->conn->write($buf);
    }
}
