<?php

namespace React\Dns\Config;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;

class FilesystemFactory
{
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function create($filename, $callback)
    {
        $that = $this;

        $this->loadEtcResolvConf($filename, function ($contents) use ($that, $callback) {
            return $that->parseEtcResolvConf($contents, $callback);
        });
    }

    public function parseEtcResolvConf($contents, $callback)
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

        $callback($config);
    }

    public function loadEtcResolvConf($filename, $callback)
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("The filename for /etc/resolv.conf given does not exist: $filename");
        }

        $fd = fopen($filename, 'r');
        stream_set_blocking($fd, 0);

        $contents = '';

        $stream = new Stream($fd, $this->loop);
        $stream->on('data', function ($data) use (&$contents) {
            $contents .= $data;
        });
        $stream->on('end', function () use (&$contents, $callback) {
            call_user_func($callback, $contents);
        });
    }
}
