<?php

namespace React\Dns;

class BinaryDumper
{
    public function toBinary(Message $message)
    {
        $data = '';

        $data .= $this->headerToBinary($message->header);
        $data .= $this->questionToBinary($message->question);

        return $data;
    }

    private function headerToBinary(array $header)
    {
        $data = '';

        $data .= pack('n', $header['id']);

        $flags = 0x00;
        $flags = ($flags << 1) | $header['qr'];
        $flags = ($flags << 4) | $header['opcode'];
        $flags = ($flags << 1) | $header['aa'];
        $flags = ($flags << 1) | $header['tc'];
        $flags = ($flags << 1) | $header['rd'];
        $flags = ($flags << 1) | $header['ra'];
        $flags = ($flags << 3) | $header['z'];
        $flags = ($flags << 4) | $header['rcode'];

        $data .= pack('n', $flags);

        $data .= pack('n', $header['qdCount']);
        $data .= pack('n', $header['anCount']);
        $data .= pack('n', $header['nsCount']);
        $data .= pack('n', $header['arCount']);

        return $data;
    }

    private function questionToBinary(array $question)
    {
        $data = '';

        foreach ($question as $q) {
            $labels = explode('.', $q['name']);
            foreach ($labels as $label) {
                $data .= chr(strlen($label)).$label;
            }
            $data .= "\x00";

            $data .= pack('n*', $q['type'], $q['class']);
        }

        return $data;
    }
}
