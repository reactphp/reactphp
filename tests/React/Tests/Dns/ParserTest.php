<?php

namespace React\Tests\Dns;

use React\Dns\Parser;
use React\Dns\Message;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideConvertTcpDumpToBinary
     */
    public function testConvertTcpDumpToBinary($expected, $data)
    {
        $this->assertSame($expected, $this->convertTcpDumpToBinary($data));
    }

    public function provideConvertTcpDumpToBinary()
    {
        return array(
            array(chr(0x72).chr(0x62), "72 62"),
            array(chr(0x72).chr(0x62).chr(0x01).chr(0x00), "72 62 01 00"),
            array(chr(0x72).chr(0x62).chr(0x01).chr(0x00).chr(0x00).chr(0x01), "72 62 01 00 00 01"),
            array(chr(0x01).chr(0x00).chr(0x01), "01 00 01"),
        );
    }

    public function testParseRequest()
    {
        $data = "";
        $data .= "72 62 01 00 00 01 00 00 00 00 00 00"; // header
        $data .= "04 69 67 6f 72 02 69 6f 00";          // question: igor.io
        $data .= "00 01 00 01";                         // question: type A, class IN

        $data = $this->convertTcpDumpToBinary($data);

        $request = new Message();

        $parser = new Parser();
        $parser->parseChunk($data, $request);

        $this->assertNotNull($request->header);
        $this->assertSame(0x7262, $request->header['id']);
        $this->assertSame(1, $request->header['qdCount']);
        $this->assertSame(0, $request->header['anCount']);
        $this->assertSame(0, $request->header['nsCount']);
        $this->assertSame(0, $request->header['arCount']);
        $this->assertSame(0, $request->header['qr']);
        $this->assertSame(Message::OPCODE_QUERY, $request->header['opcode']);
        $this->assertSame(0, $request->header['aa']);
        $this->assertSame(0, $request->header['tc']);
        $this->assertSame(1, $request->header['rd']);
        $this->assertSame(0, $request->header['ra']);
        $this->assertSame(0, $request->header['z']);

        $this->assertCount(1, $request->question);
        $this->assertSame('igor.io', $request->question[0]['name']);
        $this->assertSame(Message::TYPE_A, $request->question[0]['type']);
        $this->assertSame(Message::CLASS_IN, $request->question[0]['class']);
    }

    public function testParseResponse()
    {
        $data = "";
        $data .= "72 62 81 80 00 01 00 01 00 00 00 00"; // header
        $data .= "04 69 67 6f 72 02 69 6f 00";          // question: igor.io
        $data .= "00 01 00 01";                         // question: type A, class IN
        $data .= "c0 0c";                               // answer: offset pointer to igor.io
        $data .= "00 01 00 01";                         // answer: type A, class IN
        $data .= "00 01 51 80";                         // answer: ttl 86400
        $data .= "00 04";                               // answer: rdlength 4
        $data .= "b2 4f a9 83";                         // answer: rdata 178.79.169.131

        $data = $this->convertTcpDumpToBinary($data);

        $response = new Message();

        $parser = new Parser();
        $parser->parseChunk($data, $response);

        $this->assertNotNull($response->header);
        $this->assertSame(0x7262, $response->header['id']);
        $this->assertSame(1, $response->header['qdCount']);
        $this->assertSame(1, $response->header['anCount']);
        $this->assertSame(0, $response->header['nsCount']);
        $this->assertSame(0, $response->header['arCount']);
        $this->assertSame(1, $response->header['qr']);
        $this->assertSame(Message::OPCODE_QUERY, $response->header['opcode']);
        $this->assertSame(0, $response->header['aa']);
        $this->assertSame(0, $response->header['tc']);
        $this->assertSame(1, $response->header['rd']);
        $this->assertSame(1, $response->header['ra']);
        $this->assertSame(0, $response->header['z']);

        $this->assertCount(1, $response->question);
        $this->assertSame('igor.io', $response->question[0]['name']);
        $this->assertSame(Message::TYPE_A, $response->question[0]['type']);
        $this->assertSame(Message::CLASS_IN, $response->question[0]['class']);

        $this->assertCount(1, $response->answer);
        $this->assertSame('igor.io', $response->answer[0]->name);
        $this->assertSame(Message::TYPE_A, $response->answer[0]->type);
        $this->assertSame(Message::CLASS_IN, $response->answer[0]->class);
        $this->assertSame(86400, $response->answer[0]->ttl);
        $this->assertSame('178.79.169.131', $response->answer[0]->data);
    }

    private function convertTcpDumpToBinary($input)
    {
        // sudo ngrep -d en1 -x port 53

        return pack('H*', str_replace(' ', '', $input));
    }
}
