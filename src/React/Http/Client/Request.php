<?php

namespace React\Http\Client;

use Evenement\EventEmitter;
use Guzzle\Http\Url;
use React\Stream\Stream;
use Guzzle\Http\Message\Request as GuzzleRequest;
use React\Http\Client\Response;
use React\EventLoop\LoopInterface;
use Guzzle\Parser\Message\MessageParser;

class Request extends EventEmitter
{
    private $request;

    private $loop;

    private $stream;

    private $buffer;

    public function __construct(LoopInterface $loop, GuzzleRequest $request)
    {
        $this->loop = $loop;
        $this->request = $request;
    }

    static public function createFromUrl(LoopInterface $loop, $url)
    {
        return new static($loop, new GuzzleRequest('GET', $url));
    }

    public function send()
    {
        $this->stream = $this->connect();
        $this->stream->on('data', array($this, 'handleData'));

        $this->request->setProtocolVersion('1.0');
        $data = (string) $this->request;

        $this->stream->write($data);
    }

    public function handleData($data)
    {
        $this->buffer .= $data;

        if (false !== strpos($this->buffer, "\r\n\r\n")) {
            $response = $this->parseResponse($this->buffer);

            $this->emit('response', array($response));
            $this->stream->removeListener('data', array($this, 'handleData'));
            $this->removeAllListeners();

            $response->emit('data', array($response->getBody()));
        }
    }

    protected function parseResponse($data)
    {
        list($headers, $bodyBuffer) = explode("\r\n\r\n", $data, 2);

        $parser = new MessageParser();
        $parsed = $parser->parseResponse($headers."\r\n\r\n");

        $response = new Response(
            $this->loop,
            $this->stream,
            $parsed['protocol'],
            $parsed['version'],
            $parsed['code'],
            $parsed['reason_phrase'],
            $parsed['headers'],
            $bodyBuffer
        );

        return $response;
    }

    protected function connect()
    {
        $socketUrl = $this->getSocketUrl();

        $socket = stream_socket_client($socketUrl, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        return new Stream($socket, $this->loop);
    }

    protected function getSocketUrl()
    {
        $socketUrl = '';

        if ('https' === $this->request->getScheme()) {
            $socketUrl .= 'tls://';
        } else {
            $socketUrl .= 'tcp://';
        }

        $socketUrl .= $this->request->getHost();
        $socketUrl .= ':' . $this->request->getPort();

        return $socketUrl;
    }
}

