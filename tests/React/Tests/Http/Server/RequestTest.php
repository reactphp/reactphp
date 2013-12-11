<?php

namespace React\Tests\Http\Server;

use React\Http\Server\Request;
use React\Tests\Socket\Server\TestCase;

class RequestTest extends TestCase
{
    /** @test */
    public function expectsContinueShouldBeFalseByDefault()
    {
        $headers = array();
        $request = new Request('GET', '/', array(), '1.1', $headers);

        $this->assertFalse($request->expectsContinue());
    }

    /** @test */
    public function expectsContinueShouldBeTrueIfContinueExpected()
    {
        $headers = array('Expect' => '100-continue');
        $request = new Request('GET', '/', array(), '1.1', $headers);

        $this->assertTrue($request->expectsContinue());
    }
}
