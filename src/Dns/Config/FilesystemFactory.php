<?php

namespace React\Dns\Config;

use React\EventLoop\LoopInterface;
use React\Promise;
use React\Promise\Deferred;
use React\Stream\Stream;

class FilesystemFactory
{
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function create($filename)
    {
        return $this
            ->loadEtcResolvConf($filename)
            ->then(array($this, 'parseEtcResolvConf'));
    }

    public function parseEtcResolvConf($contents)
    {
        $nameservers = array();

        $contents = preg_replace('/^#/', '', $contents);
        $lines = preg_split('/\r?\n/is', $contents);
        foreach ($lines as $line) {
            if (preg_match('/^nameserver (.+)/', $line, $match)) {
                $nameservers[] = $match[1];
            }
        }

        $config = new Config();
        $config->nameservers = $nameservers;

        return Promise\resolve($config);
    }

    public function loadEtcResolvConf($filename)
    {
        if (!file_exists($filename)) {
            return Promise\reject(new \InvalidArgumentException("The filename for /etc/resolv.conf given does not exist: $filename"));
        }

        try {
            $deferred = new Deferred();

            $fd = fopen($filename, 'r');
            stream_set_blocking($fd, 0);

            $contents = '';

            $stream = new Stream($fd, $this->loop);
            $stream->on('data', function ($data) use (&$contents) {
                $contents .= $data;
            });
            $stream->on('end', function () use (&$contents, $deferred) {
                $deferred->resolve($contents);
            });
            $stream->on('error', function ($error) use ($deferred) {
                $deferred->reject($error);
            });

            return $deferred->promise();
        } catch (\Exception $e) {
            return Promise\reject($e);
        }
    }
}
