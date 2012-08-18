<?php

namespace React\ChildProcess;

use React\Stream\Stream as DefaultStream;

class Stream extends DefaultStream
{
    public function handleData($stream)
    {
        $data = fread($stream, $this->bufferSize);

        if ('' === $data || false === $data) {
            $this->end();
        } else {
            $this->emit('data', array($data, $this));
        }
    }
}
