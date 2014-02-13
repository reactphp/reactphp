<?php

namespace React\Dns\Protocol;

use React\Dns\Model\Message;
use React\Dns\Model\HeaderBag;

class BinaryDumper
{
    /**
     * @var
     */
    private $labelRegistry;

    public function toBinary(Message $message)
    {
        $this->labelRegistry = array();

        $data = '';

        $data .= $this->headerToBinary($message->header);
        $data .= $this->questionToBinary($message->questions);
        $data .= $this->answerToBinary($message->answers);

        return $data;
    }

    private function headerToBinary(HeaderBag $header)
    {
        $data = '';

        $data .= pack('n', $header->get('id'));

        $flags = 0x00;
        $flags = ($flags << 1) | $header->get('qr');
        $flags = ($flags << 4) | $header->get('opcode');
        $flags = ($flags << 1) | $header->get('aa');
        $flags = ($flags << 1) | $header->get('tc');
        $flags = ($flags << 1) | $header->get('rd');
        $flags = ($flags << 1) | $header->get('ra');
        $flags = ($flags << 3) | $header->get('z');
        $flags = ($flags << 4) | $header->get('rcode');

        $data .= pack('n', $flags);

        $data .= pack('n', $header->get('qdCount'));
        $data .= pack('n', $header->get('anCount'));
        $data .= pack('n', $header->get('nsCount'));
        $data .= pack('n', $header->get('arCount'));

        return $data;
    }

    private function questionToBinary(array $questions)
    {
        $data = '';

        foreach ($questions as $question) {
            $data .= $this->encodeDomainName($question['name'], true);

            $data .= pack('n*', $question['type'], $question['class']);
        }

        return $data;
    }

    private function answerToBinary(array $answers)
    {
        $data = '';

        foreach ($answers as $answer) {
            $data .= $this->encodeDomainName($answer->name, true);

            $rdata = '';
            if (Message::TYPE_A === $answer->type) {
                $rdata .= $this->encodeIpv4Address($answer->data);
            }

            if (Message::TYPE_CNAME === $answer->type) {
                $rdata .= $this->encodeDomainName($answer->data, true);
            }

            $data .= pack('n', $answer->type);
            $data .= pack('n', $answer->class);
            $data .= pack('N', $answer->ttl);
            $data .= pack('n', strlen($rdata));
            $data .= $rdata;
        }

        return $data;
    }

    private function encodeDomainName($domainName, $compress = false)
    {
        $data = '';

        $labels = explode('.', $domainName);

        if ($compress) {
            $packetIndex = 12;

            while (!empty($labels)) {
                $part = implode('.', $labels);

                if (!isset($this->labelRegistry[$part])) {
                    $this->labelRegistry[$part] = $packetIndex;

                    $label = array_shift($labels);
                    $length = strlen($label);

                    $data .= chr($length) . $label;
                    $packetIndex += $length + 1;
                } else {
                    $data .= pack('n', 0b1100000000000000 | $this->labelRegistry[$part]);
                    break;
                }
            }

            if (!$labels) {
                $data .= "\x00";
            }
        } else {
            foreach ($labels as $label) {
                $data .= chr(strlen($label)).$label;
            }

            $data .= "\x00";
        }

        return $data;
    }

    private function encodeIpv4Address($ipv4Address)
    {
        $octets = explode('.', $ipv4Address);
        return pack('C*', $octets[0], $octets[1], $octets[2], $octets[3]);
    }
}
