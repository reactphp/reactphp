<?php

namespace React\Tests\Http;

use React\Http\Request;
use React\Tests\Socket\TestCase;

class RequestTest extends TestCase
{
    /** @test */
    public function expectsContinueShouldBeFalseByDefault()
    {
        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $headers = array();
        $request = new Request($conn, 'GET', '/', array(), '1.1', $headers);

        $this->assertFalse($request->expectsContinue());
    }

    /** @test */
    public function expectsContinueShouldBeTrueIfContinueExpected()
    {
        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $headers = array('Expect' => '100-continue');
        $request = new Request($conn, 'GET', '/', array(), '1.1', $headers);

        $this->assertTrue($request->expectsContinue());
    }
}
