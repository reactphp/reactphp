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

    public function testParseQuestionWithTwoQuestions()
    {
        $data = "";
        $data .= "04 69 67 6f 72 02 69 6f 00";              // question: igor.io
        $data .= "00 01 00 01";                             // question: type A, class IN
        $data .= "03 77 77 77 04 69 67 6f 72 02 69 6f 00";  // question: www.igor.io
        $data .= "00 01 00 01";                             // question: type A, class IN

        $data = $this->convertTcpDumpToBinary($data);

        $request = new Message();
        $request->header->set('qdCount', 2);
        $request->data = $data;

        $parser = new Parser();
        $parser->parseQuestion($request);

        $this->assertCount(2, $request->questions);
        $this->assertSame('igor.io', $request->questions[0]['name']);
        $this->assertSame(Message::TYPE_A, $request->questions[0]['type']);
        $this->assertSame(Message::CLASS_IN, $request->questions[0]['class']);
        $this->assertSame('www.igor.io', $request->questions[1]['name']);
        $this->assertSame(Message::TYPE_A, $request->questions[1]['type']);
        $this->assertSame(Message::CLASS_IN, $request->questions[1]['class']);
    }

    public function testParseAnswerWithInlineData()
    {
        $data = "";
        $data .= "04 69 67 6f 72 02 69 6f 00";          // answer: igor.io
        $data .= "00 01 00 01";                         // answer: type A, class IN
        $data .= "00 01 51 80";                         // answer: ttl 86400
        $data .= "00 04";                               // answer: rdlength 4
        $data .= "b2 4f a9 83";                         // answer: rdata 178.79.169.131

        $data = $this->convertTcpDumpToBinary($data);

        $response = new Message();
        $response->header->set('anCount', 1);
        $response->data = $data;

        $parser = new Parser();
        $parser->parseAnswer($response);

        $this->assertCount(1, $response->answers);
        $this->assertSame('igor.io', $response->answers[0]->name);
        $this->assertSame(Message::TYPE_A, $response->answers[0]->type);
        $this->assertSame(Message::CLASS_IN, $response->answers[0]->class);
        $this->assertSame(86400, $response->answers[0]->ttl);
        $this->assertSame('178.79.169.131', $response->answers[0]->data);
    }

    public function testParseResponseWithCnameAndOffsetPointers()
    {
        $data = "";
        $data .= "9e 8d 81 80 00 01 00 01 00 00 00 00";                 // header
        $data .= "04 6d 61 69 6c 06 67 6f 6f 67 6c 65 03 63 6f 6d 00";  // question: mail.google.com
        $data .= "00 05 00 01";                                         // question: type CNAME, class IN
        $data .= "c0 0c";                                               // answer: offset pointer to mail.google.com
        $data .= "00 05 00 01";                                         // answer: type CNAME, class IN
        $data .= "00 00 a8 9c";                                         // answer: ttl 43164
        $data .= "00 0f";                                               // answer: rdlength 15
        $data .= "0a 67 6f 6f 67 6c 65 6d 61 69 6c 01 6c";              // answer: rdata googlemail.l.
        $data .= "c0 11";                                               // answer: rdata offset pointer to google.com

        $data = $this->convertTcpDumpToBinary($data);

        $response = new Message();

        $parser = new Parser();
        $parser->parseChunk($data, $response);

        $this->assertCount(1, $response->questions);
        $this->assertSame('mail.google.com', $response->questions[0]['name']);
        $this->assertSame(Message::TYPE_CNAME, $response->questions[0]['type']);
        $this->assertSame(Message::CLASS_IN, $response->questions[0]['class']);

        $this->assertCount(1, $response->answers);
        $this->assertSame('mail.google.com', $response->answers[0]->name);
        $this->assertSame(Message::TYPE_CNAME, $response->answers[0]->type);
        $this->assertSame(Message::CLASS_IN, $response->answers[0]->class);
        $this->assertSame(43164, $response->answers[0]->ttl);
        $this->assertSame('googlemail.l.google.com', $response->answers[0]->data);
    }

    public function testParseResponseWithTwoAnswers()
    {
        $data = "";
        $data .= "bc 73 81 80 00 01 00 02 00 00 00 00";                 // header
        $data .= "02 69 6f 0d 77 68 6f 69 73 2d 73 65 72 76 65 72 73 03 6e 65 74 00";
                                                                        // question: io.whois-servers.net
        $data .= "00 01 00 01";                                         // question: type A, class IN
        $data .= "c0 0c";                                               // answer: offset pointer to io.whois-servers.net
        $data .= "00 05 00 01";                                         // answer: type CNAME, class IN
        $data .= "00 00 00 29";                                         // answer: ttl 41
        $data .= "00 0e";                                               // answer: rdlength 14
        $data .= "05 77 68 6f 69 73 03 6e 69 63 02 69 6f 00";           // answer: rdata whois.nic.io
        $data .= "c0 32";                                               // answer: offset pointer to whois.nic.io
        $data .= "00 01 00 01";                                         // answer: type CNAME, class IN
        $data .= "00 00 0d f7";                                         // answer: ttl 3575
        $data .= "00 04";                                               // answer: rdlength 4
        $data .= "c1 df 4e 98";                                         // answer: rdata 193.223.78.152

        $data = $this->convertTcpDumpToBinary($data);

        $response = new Message();

        $parser = new Parser();
        $parser->parseChunk($data, $response);

        $this->assertCount(1, $response->questions);
        $this->assertSame('io.whois-servers.net', $response->questions[0]['name']);
        $this->assertSame(Message::TYPE_A, $response->questions[0]['type']);
        $this->assertSame(Message::CLASS_IN, $response->questions[0]['class']);

        $this->assertCount(2, $response->answers);

        $this->assertSame('io.whois-servers.net', $response->answers[0]->name);
        $this->assertSame(Message::TYPE_CNAME, $response->answers[0]->type);
        $this->assertSame(Message::CLASS_IN, $response->answers[0]->class);
        $this->assertSame(41, $response->answers[0]->ttl);
        $this->assertSame('whois.nic.io', $response->answers[0]->data);

        $this->assertSame('whois.nic.io', $response->answers[1]->name);
        $this->assertSame(Message::TYPE_A, $response->answers[1]->type);
        $this->assertSame(Message::CLASS_IN, $response->answers[1]->class);
        $this->assertSame(3575, $response->answers[1]->ttl);
        $this->assertSame('193.223.78.152', $response->answers[1]->data);
    }

    private function convertTcpDumpToBinary($input)
    {
        // sudo ngrep -d en1 -x port 53

        return pack('H*', str_replace(' ', '', $input));
    }
}
