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
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN);

        $cache = new RecordCache();
        $cachedRecord = $cache->lookup($query);

        $this->assertSame(null, $cachedRecord);
    }

    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function storeRecordShouldMakeLookupSucceed()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN);

        $cache = new RecordCache();
        $cache->storeRecord(new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 8400, '178.79.169.131'));
        $cachedRecord = $cache->lookup($query);

        $this->assertSame('178.79.169.131', $cachedRecord->data);
    }

    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function storeRecordTwiceShouldOverridePreviousValue()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN);

        $cache = new RecordCache();
        $cache->storeRecord(new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 8400, '178.79.169.131'));
        $cache->storeRecord(new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 8400, '178.79.169.132'));
        $cachedRecord = $cache->lookup($query);

        $this->assertSame('178.79.169.132', $cachedRecord->data);
    }

    /**
    * @covers React\Dns\Query\RecordCache
    * @test
    */
    public function storeResponseMessageShouldStoreAllAnswerValues()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN);

        $response = new Message();
        $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 8400, '178.79.169.131');
        $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 8400, '178.79.169.132');
        $response->prepare();

        $cache = new RecordCache();
        $cache->storeResponseMessage($response);
        $cachedRecord = $cache->lookup($query);

        $this->assertSame('178.79.169.132', $cachedRecord->data);
    }
}
