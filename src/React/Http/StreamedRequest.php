<?php

namespace React\Http;

use Symfony\Component\HttpFoundation\Request;

class StreamedRequest extends Request
{
    private $onData = array();
    private $onBody = array();

    public function getContent($asResource = false)
    {
        throw new \LogicException('getContent() cannot be called on streamed requests.');
    }

    public function onData($callback)
    {
        $this->onData[] = $callback;
    }

    public function onBody($callback)
    {
        $this->onBody[] = $callback;
    }

    public function emitData($data)
    {
        $this->callListeners($this->onData, $data);
    }

    public function emitBody($body)
    {
        $this->callListeners($this->onBody, $body);
    }

    private function callListeners($callbacks, $arg)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $arg);
        }
    }
}
