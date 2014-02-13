<?php

namespace React\Tests\Dns\Protocol;

use React\Dns\Model\Record;
use React\Dns\Protocol\BinaryDumper;
use React\Dns\Model\Message;
use React\Dns\Protocol\Parser;

class BinaryDumperTest extends \PHPUnit_Framework_TestCase
{
    public function testRequestToBinary()
    {
        $data = "";
        $data .= "72 62 01 00 00 01 00 00 00 00 00 00"; // header
        $data .= "04 69 67 6f 72 02 69 6f 00";          // question: igor.io
        $data .= "00 01 00 01";                         // question: type A, class IN

        $expected = $this->formatHexDump(str_replace(' ', '', $data), 2);

        $request = new Message();
        $request->header->set('id', 0x7262);
        $request->header->set('rd', 1);

        $request->questions[] = array(
            'name'  => 'igor.io',
            'type'  => Message::TYPE_A,
            'class' => Message::CLASS_IN,
        );

        $request->prepare();

        $this->assertFalse($request->header->isResponse());

        $dumper = new BinaryDumper();
        $data = $dumper->toBinary($request);
        $data = $this->convertBinaryToHexDump($data);

        $this->assertSame($expected, $data);
    }

    public function testResponseToBinary()
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

        $expected = $this->formatHexDump(str_replace(' ', '', $data), 2);

        $response = new Message();
        $response->header->set('id', 0x7262);
        $response->header->set('rd', 1);
        $response->header->set('qr', 1);
        $response->header->set('ra', 1);
        $response->header->set('opcode', Message::OPCODE_QUERY);

        $response->questions[] = array(
            'name'  => 'igor.io',
            'type'  => Message::TYPE_A,
            'class' => Message::CLASS_IN,
        );

        $response->answers[] = new Record(
            'igor.io',
            Message::TYPE_A,
            Message::CLASS_IN,
            86400,
            '178.79.169.131'
        );

        $response->prepare();

        $this->assertTrue($response->header->isResponse());

        $dumper = new BinaryDumper();
        $data = $dumper->toBinary($response);
        $data = $this->convertBinaryToHexDump($data);

        $this->assertSame($expected, $data);
    }

    public function testResponseWithCnameAndOffsetPointersToBinary()
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

        $expected = $this->formatHexDump(str_replace(' ', '', $data), 2);

        $response = new Message();
        $response->header->set('id', 0x9e8d);
        $response->header->set('rd', 1);
        $response->header->set('qr', 1);
        $response->header->set('ra', 1);
        $response->header->set('opcode', Message::OPCODE_QUERY);

        $response->questions[] = array(
            'name'  => 'mail.google.com',
            'type'  => Message::TYPE_CNAME,
            'class' => Message::CLASS_IN,
        );

        $response->answers[] = new Record(
            'mail.google.com',
            Message::TYPE_CNAME,
            Message::CLASS_IN,
            43164,
            'googlemail.l.google.com'
        );

        $response->prepare();

        $this->assertTrue($response->header->isResponse());

        $dumper = new BinaryDumper();
        $data = $dumper->toBinary($response);
        $data = $this->convertBinaryToHexDump($data);

        $this->assertSame($expected, $data);
    }

    public function testResponseWithTwoAnswersToBinary()
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

        $expected = $this->formatHexDump(str_replace(' ', '', $data), 2);

        $response = new Message();
        $response->header->set('id', 0xbc73);
        $response->header->set('rd', 1);
        $response->header->set('qr', 1);
        $response->header->set('ra', 1);
        $response->header->set('opcode', Message::OPCODE_QUERY);

        $response->questions[] = array(
            'name'  => 'io.whois-servers.net',
            'type'  => Message::TYPE_A,
            'class' => Message::CLASS_IN,
        );

        $response->answers[] = new Record(
            'io.whois-servers.net',
            Message::TYPE_CNAME,
            Message::CLASS_IN,
            41,
            'whois.nic.io'
        );

        $response->answers[] = new Record(
            'whois.nic.io',
            Message::TYPE_A,
            Message::CLASS_IN,
            3575,
            '193.223.78.152'
        );

        $response->prepare();

        $this->assertTrue($response->header->isResponse());

        $dumper = new BinaryDumper();
        $data = $dumper->toBinary($response);
        $data = $this->convertBinaryToHexDump($data);

        $this->assertSame($expected, $data);
    }

    private function convertBinaryToHexDump($input)
    {
        return $this->formatHexDump(implode('', unpack('H*', $input)));
    }

    private function formatHexDump($input)
    {
        return implode(' ', str_split($input, 2));
    }
}
