<?php

namespace React\Tests\Dns\Query;

use React\Dns\Query\RecordBag;
use React\Dns\Model\Message;
use React\Dns\Model\Record;

class RecordBagTest extends \PHPUnit_Framework_TestCase
{
    /**
    * @covers React\Dns\Query\RecordBag
    * @test
    */
    public function emptyBagShouldBeEmpty()
    {
        $recordBag = new RecordBag();

        $this->assertSame(array(), $recordBag->all());
    }

    /**
    * @covers React\Dns\Query\RecordBag
    * @test
    */
    public function setShouldSetTheValue()
    {
        $currentTime = 1345656451;

        $recordBag = new RecordBag();
        $recordBag->set($currentTime, new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600));

        $records = $recordBag->all();
        $this->assertCount(1, $records);
        $this->assertSame('igor.io', $records[0]->name);
        $this->assertSame(Message::TYPE_A, $records[0]->type);
        $this->assertSame(Message::CLASS_IN, $records[0]->class);
    }

    /**
    * @covers React\Dns\Query\RecordBag
    * @test
    */
    public function setShouldSetManyValues()
    {
        $currentTime = 1345656451;

        $recordBag = new RecordBag();
        $recordBag->set($currentTime, new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'));
        $recordBag->set($currentTime, new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132'));

        $records = $recordBag->all();
        $this->assertCount(2, $records);
        $this->assertSame('igor.io', $records[0]->name);
        $this->assertSame(Message::TYPE_A, $records[0]->type);
        $this->assertSame(Message::CLASS_IN, $records[0]->class);
        $this->assertSame('178.79.169.131', $records[0]->data);
        $this->assertSame('igor.io', $records[1]->name);
        $this->assertSame(Message::TYPE_A, $records[1]->type);
        $this->assertSame(Message::CLASS_IN, $records[1]->class);
        $this->assertSame('178.79.169.132', $records[1]->data);
    }
}
