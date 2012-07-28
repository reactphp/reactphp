<?php

namespace React\Http\Client;

use Evenement\EventEmitter;
use Guzzle\Http\Url;
use React\Stream\Stream;
use Guzzle\Http\Message\Request as GuzzleRequest;
use React\Http\Client\Response;
use React\EventLoop\LoopInterface;
use Guzzle\Parser\Message\MessageParser;
use React\Http\Client\ConnectionManagerInterface;
use React\Http\Client\ResponseHeaderParser;

class Request extends EventEmitter
{
    private $request;
    private $loop;
    private $connectionManager;
    private $stream;
    private $buffer;
    private $responseFactory;
    private $response;

    public function __construct(LoopInterface $loop, ConnectionManagerInterface $connectionManager, GuzzleRequest $request)
    {
        $this->loop = $loop;
        $this->connectionManager = $connectionManager;
        $this->request = $request;
    }

    public function send()
    {
        if (!$this->stream = $this->connect()) {
            return;
        }

        $this->stream->on('data', array($this, 'handleData'));
        $this->stream->on('end', array($this, 'handleEnd'));
        $this->stream->on('error', array($this, 'handleError'));

        $this->request->setProtocolVersion('1.0');
        $data = (string) $this->request;

        $this->stream->write($data);
    }

    public function handleData($data)
    {
        $this->buffer .= $data;

        if (false !== strpos($this->buffer, "\r\n\r\n")) {

            list($response, $body) = $this->parseResponse($this->buffer);

            $this->stream->removeListener('data', array($this, 'handleData'));
            $this->stream->removeListener('end', array($this, 'handleEnd'));
            $this->stream->removeListener('error', array($this, 'handleError'));

            $this->emit('response', array($response));

            $response->emit('data', array($body));
        }
    }

    public function handleEnd()
    {
        $this->emit('error', array($this));
    }

    public function handleError()
    {
        $this->emit('error', array($this));
    }

    protected function parseResponse($data)
    {
        $parser = new MessageParser();
        $parsed = $parser->parseResponse($data);

        $response = $this->getResponseFactory()->__invoke(
            $parsed['protocol'],
            $parsed['version'],
            $parsed['code'],
            $parsed['reason_phrase'],
            $parsed['headers']
        );

        return array($response, $parsed['body']);
    }

    protected function connect()
    {
        $host = $this->request->getHost();
        $port = $this->request->getPort();
        $https = 'https' === $this->request->getScheme();
        $connectionManager = $this->connectionManager;

        $stream = $this->connectionManager->getConnection($host, $port, $https);

        if (!$stream) {
            $this->emit('error', array($this));
            return null;
        }

        return $stream;
    }

    public function setResponseFactory($factory)
    {
        $this->responseFactory = $factory;
    }

    public function getResponseFactory()
    {
        if (null === $factory = $this->responseFactory) {

            $loop = $this->loop;
            $stream = $this->stream;

            $factory = function ($protocol, $version, $code, $reasonPhrase, $headers) use ($loop, $stream) {
                return new Response(
                    $loop,
                    $stream,
                    $protocol,
                    $version,
                    $code,
                    $reasonPhrase,
                    $headers
                );
            };

            $this->responseFactory = $factory;
        }

        return $factory;
    }
}

