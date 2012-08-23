<?php

namespace React\Tests\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Dns\Query\RecordCache;
use React\Dns\Query\Query;

class RecordCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function lookupOnEmptyCacheShouldReturnNull()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);

        $cache = new RecordCache();
        $cachedRecords = $cache->lookup($query);

        $this->assertSame(array(), $cachedRecords);
    }

    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function storeRecordShouldMakeLookupSucceed()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);

        $cache = new RecordCache();
        $cache->storeRecord($query->currentTime, new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'));
        $cachedRecords = $cache->lookup($query);

        $this->assertCount(1, $cachedRecords);
        $this->assertSame('178.79.169.131', $cachedRecords[0]->data);
    }

    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function storeTwoRecordsShouldReturnBoth()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);

        $cache = new RecordCache();
        $cache->storeRecord($query->currentTime, new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'));
        $cache->storeRecord($query->currentTime, new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132'));
        $cachedRecords = $cache->lookup($query);

        $this->assertCount(2, $cachedRecords);
        $this->assertSame('178.79.169.131', $cachedRecords[0]->data);
        $this->assertSame('178.79.169.132', $cachedRecords[1]->data);
    }

    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function storeResponseMessageShouldStoreAllAnswerValues()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);

        $response = new Message();
        $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131');
        $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132');
        $response->prepare();

        $cache = new RecordCache();
        $cache->storeResponseMessage($query->currentTime, $response);
        $cachedRecords = $cache->lookup($query);

        $this->assertCount(2, $cachedRecords);
        $this->assertSame('178.79.169.131', $cachedRecords[0]->data);
        $this->assertSame('178.79.169.132', $cachedRecords[1]->data);
    }

    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function expireShouldExpireDeadRecords()
    {
        $cachedTime = 1345656451;
        $currentTime = $cachedTime + 3605;

        $cache = new RecordCache();
        $cache->storeRecord($cachedTime, new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'));
        $cache->expire($currentTime);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, $currentTime);
        $cachedRecords = $cache->lookup($query);

        $this->assertCount(0, $cachedRecords);
    }
}
