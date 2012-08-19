<?php

namespace React\Tests\Dns\Protocol;

use React\Dns\Protocol\Parser;
use React\Dns\Model\Message;

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

        $header = $request->header;
        $this->assertSame(0x7262, $header->get('id'));
        $this->assertSame(1, $header->get('qdCount'));
        $this->assertSame(0, $header->get('anCount'));
        $this->assertSame(0, $header->get('nsCount'));
        $this->assertSame(0, $header->get('arCount'));
        $this->assertSame(0, $header->get('qr'));
        $this->assertSame(Message::OPCODE_QUERY, $header->get('opcode'));
        $this->assertSame(0, $header->get('aa'));
        $this->assertSame(0, $header->get('tc'));
        $this->assertSame(1, $header->get('rd'));
        $this->assertSame(0, $header->get('ra'));
        $this->assertSame(0, $header->get('z'));
        $this->assertSame(Message::RCODE_OK, $header->get('rcode'));

        $this->assertCount(1, $request->questions);
        $this->assertSame('igor.io', $request->questions[0]['name']);
        $this->assertSame(Message::TYPE_A, $request->questions[0]['type']);
        $this->assertSame(Message::CLASS_IN, $request->questions[0]['class']);
    }

    public function testParseRequestWithTwoQuestions()
    {
        $data = "";
        $data .= "72 62 01 00 00 02 00 00 00 00 00 00";     // header
        $data .= "04 69 67 6f 72 02 69 6f 00";              // question: igor.io
        $data .= "00 01 00 01";                             // question: type A, class IN
        $data .= "03 77 77 77 04 69 67 6f 72 02 69 6f 00";  // question: www.igor.io
        $data .= "00 01 00 01";                             // question: type A, class IN

        $data = $this->convertTcpDumpToBinary($data);

        $request = new Message();

        $parser = new Parser();
        $parser->parseChunk($data, $request);

        $header = $request->header;
        $this->assertSame(0x7262, $header->get('id'));
        $this->assertSame(2, $header->get('qdCount'));
        $this->assertSame(0, $header->get('anCount'));
        $this->assertSame(0, $header->get('nsCount'));
        $this->assertSame(0, $header->get('arCount'));
        $this->assertSame(0, $header->get('qr'));
        $this->assertSame(Message::OPCODE_QUERY, $header->get('opcode'));
        $this->assertSame(0, $header->get('aa'));
        $this->assertSame(0, $header->get('tc'));
        $this->assertSame(1, $header->get('rd'));
        $this->assertSame(0, $header->get('ra'));
        $this->assertSame(0, $header->get('z'));
        $this->assertSame(Message::RCODE_OK, $header->get('rcode'));

        $this->assertCount(2, $request->questions);
        $this->assertSame('igor.io', $request->questions[0]['name']);
        $this->assertSame(Message::TYPE_A, $request->questions[0]['type']);
        $this->assertSame(Message::CLASS_IN, $request->questions[0]['class']);
        $this->assertSame('www.igor.io', $request->questions[1]['name']);
        $this->assertSame(Message::TYPE_A, $request->questions[1]['type']);
        $this->assertSame(Message::CLASS_IN, $request->questions[1]['class']);
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

        $header = $response->header;
        $this->assertSame(0x7262, $header->get('id'));
        $this->assertSame(1, $header->get('qdCount'));
        $this->assertSame(1, $header->get('anCount'));
        $this->assertSame(0, $header->get('nsCount'));
        $this->assertSame(0, $header->get('arCount'));
        $this->assertSame(1, $header->get('qr'));
        $this->assertSame(Message::OPCODE_QUERY, $header->get('opcode'));
        $this->assertSame(0, $header->get('aa'));
        $this->assertSame(0, $header->get('tc'));
        $this->assertSame(1, $header->get('rd'));
        $this->assertSame(1, $header->get('ra'));
        $this->assertSame(0, $header->get('z'));
        $this->assertSame(Message::RCODE_OK, $header->get('rcode'));

        $this->assertCount(1, $response->questions);
        $this->assertSame('igor.io', $response->questions[0]['name']);
        $this->assertSame(Message::TYPE_A, $response->questions[0]['type']);
        $this->assertSame(Message::CLASS_IN, $response->questions[0]['class']);

        $this->assertCount(1, $response->answers);
        $this->assertSame('igor.io', $response->answers[0]->name);
        $this->assertSame(Message::TYPE_A, $response->answers[0]->type);
        $this->assertSame(Message::CLASS_IN, $response->answers[0]->class);
        $this->assertSame(86400, $response->answers[0]->ttl);
        $this->assertSame('178.79.169.131', $response->answers[0]->data);
    }

    private function convertTcpDumpToBinary($input)
    {
        // sudo ngrep -d en1 -x port 53

        return pack('H*', str_replace(' ', '', $input));
    }
}
