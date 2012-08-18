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
    private $closed = false;

    public function __construct(LoopInterface $loop, ConnectionManagerInterface $connectionManager, GuzzleRequest $request)
    {
        $this->loop = $loop;
        $this->connectionManager = $connectionManager;
        $this->request = $request;
    }

    public function end($data = null)
    {
        $that = $this;
        $request = $this->request;
        $streamRef = &$this->stream;

        if (null !== $data && !is_scalar($data)) {
            throw new \InvalidArgumentException('$data must be null or scalar');
        }

        $this->connect(function($stream, \Exception $error = null) use ($that, $request, &$streamRef, $data) {
            if (!$stream) {
                $that->closeError(new \RuntimeException(
                    "connection failed",
                    0,
                    $error
                ));
                return;
            }

            $streamRef = $stream;

            $stream->on('data', array($that, 'handleData'));
            $stream->on('end', array($that, 'handleEnd'));
            $stream->on('error', array($that, 'handleError'));

            $request->setProtocolVersion('1.0');
            $headers = (string) $request;

            if (null !== $data) {
                $headers .= $data;
            }

            $stream->write($headers);
        });
    }

    public function handleData($data)
    {
        $this->buffer .= $data;

        if (false !== strpos($this->buffer, "\r\n\r\n")) {

            list($response, $body) = $this->parseResponse($this->buffer);

            $this->stream->removeListener('data', array($this, 'handleData'));
            $this->stream->removeListener('end', array($this, 'handleEnd'));
            $this->stream->removeListener('error', array($this, 'handleError'));

            $this->response = $response;
            $that = $this;

            $response->on('end', function() use ($that) {
                $that->close();
            });
            $response->on('error', function(\Exception $error) use ($that) {
                $that->closeError(new \RuntimeException(
                    "response error",
                    0,
                    $error
                ));
            });

            $this->emit('response', array($response, $this));

            $response->emit('data', array($body));
        }
    }

    public function handleEnd()
    {
        $this->closeError(new \RuntimeException(
            "connection closed before receiving response"
        ));
    }

    public function handleError($error)
    {
        $this->closeError(new \RuntimeException(
            "stream error",
            0,
            $error
        ));
    }

    public function closeError(\Exception $error)
    {
        if ($this->closed) {
            return;
        }
        $this->emit('error', array($error, $this));
        $this->close($error);
    }

    public function close(\Exception $error = null)
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        if ($this->stream) {
            $this->stream->close();
        }

        $this->emit('end', array($error, $this->response, $this));
    }

    protected function parseResponse($data)
    {
        $parser = new MessageParser();
        $parsed = $parser->parseResponse($data);

        $factory = $this->getResponseFactory();

        $response = $factory(
            $parsed['protocol'],
            $parsed['version'],
            $parsed['code'],
            $parsed['reason_phrase'],
            $parsed['headers']
        );

        return array($response, $parsed['body']);
    }

    protected function connect($callback)
    {
        $host = $this->request->getHost();
        $port = $this->request->getPort();
        $connectionManager = $this->connectionManager;
        $that = $this;

        $connectionManager->getConnection(function($stream) use ($that, $callback) {
            call_user_func($callback, $stream);
        }, $host, $port);
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

