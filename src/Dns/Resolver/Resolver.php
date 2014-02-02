<?php

namespace React\Dns\Resolver;

use React\Dns\Query\ExecutorInterface;
use React\Dns\Query\Query;
use React\Dns\RecordNotFoundException;
use React\Dns\Model\Message;

class Resolver
{
    private $nameserver;
    private $executor;

    public function __construct($nameserver, ExecutorInterface $executor)
    {
        $this->nameserver = $nameserver;
        $this->executor = $executor;
    }

    public function resolve($domain)
    {
        $query = new Query($domain, Message::TYPE_A, Message::CLASS_IN, time());

        return $this->executor
            ->query($this->nameserver, $query)
            ->then(function (Message $response) use ($query) {
                return $this->extractAddress($query, $response);
            });
    }

    public function extractAddress(Query $query, Message $response)
    {
        $answers = $response->answers;

        $addresses = $this->resolveAliases($answers, $query->name);

        if (0 === count($addresses)) {
            $message = 'DNS Request did not return valid answer.';
            throw new RecordNotFoundException($message);
        }

        $address = $addresses[array_rand($addresses)];
        return $address;
    }

    public function resolveAliases(array $answers, $name)
    {
        $named = $this->filterByName($answers, $name);
        $aRecords = $this->filterByType($named, Message::TYPE_A);
        $cnameRecords = $this->filterByType($named, Message::TYPE_CNAME);

        if ($aRecords) {
            return $this->mapRecordData($aRecords);
        }

        if ($cnameRecords) {
            $aRecords = array();

            $cnames = $this->mapRecordData($cnameRecords);
            foreach ($cnames as $cname) {
                $targets = $this->filterByName($answers, $cname);
                $aRecords = array_merge(
                    $aRecords,
                    $this->resolveAliases($answers, $cname)
                );
            }

            return $aRecords;
        }

        return array();
    }

    private function filterByName(array $answers, $name)
    {
        return $this->filterByField($answers, 'name', $name);
    }

    private function filterByType(array $answers, $type)
    {
        return $this->filterByField($answers, 'type', $type);
    }

    private function filterByField(array $answers, $field, $value)
    {
        return array_filter($answers, function ($answer) use ($field, $value) {
            return $value === $answer->$field;
        });
    }

    private function mapRecordData(array $records)
    {
        return array_map(function ($record) {
            return $record->data;
        }, $records);
    }
}
