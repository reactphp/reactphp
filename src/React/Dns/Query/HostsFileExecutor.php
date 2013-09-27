<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Dns\Query\ExecutorInterface;
use React\Dns\Query\Query;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\When;
use React\Stream\Stream;

class HostsFileExecutor implements ExecutorInterface
{
    private $loop;
    private $executor;
    private $byName;
    private $path;
    private $loadingPromise;

    public function __construct(LoopInterface $loop, ExecutorInterface $executor, $path = "/etc/hosts")
    {
        $this->loop = $loop;
        $this->executor = $executor;
        $this->path = $path;
    }

    public function query($nameserver, Query $query)
    {
        $that = $this;
        $executor = $this->executor;

        return $this
            ->loadHosts()
            ->then(function () use ($that, $query) {
                return $that->doQuery($query);
            })
            ->then(null, function () use ($query, $nameserver, $executor) {
                return $executor->query($nameserver, $query);
            });
    }

    public function doQuery(Query $query)
    {
        if (Message::TYPE_A !== $query->type) {
            return When::reject();
        }

        if (!isset($this->byName[$query->name])) {
            return When::reject();
        }

        $records = $this->byName[$query->name];

        $response = $this->buildResponse($query, $records);

        return When::resolve($response);
    }

    private function loadHosts()
    {
        if (null !== $this->loadingPromise) {
            return $this->loadingPromise;
        }

        $this->byName = array();

        $deferred = new Deferred();
        $this->loadingPromise = $deferred->promise();

        $that = $this;

        try {

            if (!file_exists($this->path)) {
                throw new \InvalidArgumentException(sprintf("Hosts file does not exist: %s", $this->path));
            }

            $fd = fopen($this->path, "rb");

            if (!$fd) {
                throw new \InvalidArgumentException(sprintf("Unable to open hosts file: %s", $this->path));
            }

            stream_set_blocking($fd, 0);

            $contents = '';

            $stream = new Stream($fd, $this->loop);
            $stream->on('data', function ($data) use (&$contents, $that) {
                $contents = $that->parseHosts($contents . $data);
            });
            $stream->on('end', function () use (&$contents, $deferred, $that) {
                $that->parseHosts($contents . "\n");
                $deferred->resolve($contents);
            });
            $stream->on('error', function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        } catch(\Exception $e) {
            $deferred->reject($e);
        }

        return $this->loadingPromise;
    }

    public function parseHosts($contents)
    {
        $offset = 0;
        $end = 0;
        while (false !== $end = strpos($contents, "\n", $offset)) {

            $line = substr($contents, $offset, $end-$offset);
            $offset = $end + 1;

            if (false !== $i = strpos($line, '#')) {
                $line = substr($line, 0, $i);
            }

            $fields = preg_split("#[ \t]+#", $line, -1, PREG_SPLIT_NO_EMPTY);

            if (count($fields) < 2) {
                continue;
            }
        
            $addr = $fields[0];

            if (false === filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }

            for ($i = 1, $l = count($fields); $i < $l; ++$i) {
                $h = $fields[$i];
                $this->byName[$h][] = new Record($h, Message::TYPE_A, Message::CLASS_IN, 300, $addr);
            }
        }

        return substr($contents, $offset);
    }

    public function buildResponse(Query $query, array $records)
    {
        $response = new Message();

        $response->header->set('id', $this->generateId());
        $response->header->set('qr', 1);
        $response->header->set('opcode', Message::OPCODE_QUERY);
        $response->header->set('rd', 1);
        $response->header->set('rcode', Message::RCODE_OK);

        $response->questions[] = new Record($query->name, $query->type, $query->class);
        $response->answers = $records;

        $response->prepare();

        return $response;
    }

    protected function generateId()
    {
        return mt_rand(0, 0xffff);
    }
}
