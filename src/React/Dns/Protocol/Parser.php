<?php

namespace React\Dns\Protocol;

use React\Dns\Model\Message;
use React\Dns\Model\Record;

/**
 * DNS protocol parser
 *
 * Obsolete and uncommon types and classes are not implemented.
 */
class Parser
{
    public function parseChunk($data, Message $message)
    {
        $message->data .= $data;

        if (!$message->header->get('id')) {
            if (!$this->parseHeader($message)) {
                return;
            }
        }

        if ($message->header->get('qdCount') != count($message->questions)) {
            if (!$this->parseQuestion($message)) {
                return;
            }
        }

        if ($message->header->get('anCount') != count($message->answers)) {
            if (!$this->parseAnswer($message)) {
                return;
            }
        }

        return $message;
    }

    private function parseHeader(Message $message)
    {
        if (strlen($message->data) < 12) {
            return;
        }

        $header = substr($message->data, 0, 12);
        $message->data = substr($message->data, 12);

        list($id, $fields, $qdCount, $anCount, $nsCount, $arCount) = array_merge(unpack('n*', $header));

        $rcode = $fields & chr(bindec('1111'));
        $z = ($fields >> 4) & chr(bindec('111'));
        $ra = ($fields >> 7) & 1;
        $rd = ($fields >> 8) & 1;
        $tc = ($fields >> 9) & 1;
        $aa = ($fields >> 10) & 1;
        $opcode = ($fields >> 11) & chr(bindec('1111'));
        $qr = ($fields >> 15) & 1;

        $vars = compact('id', 'qdCount', 'anCount', 'nsCount', 'arCount',
                            'qr', 'opcode', 'aa', 'tc', 'rd', 'ra', 'z', 'rcode');


        foreach ($vars as $name => $value) {
            $message->header->set($name, $value);
        }

        return $message;
    }

    private function parseQuestion(Message $message)
    {
        if (strlen($message->data) < 2) {
            return;
        }

        $labels = array();

        $consumed = 0;

        $length = ord(substr($message->data, $consumed, 1));
        $consumed += 1;

        if (strlen($message->data) - $consumed < $length) {
            return;
        }

        while ($length !== 0) {
            $labels[] = substr($message->data, $consumed, $length);
            $consumed += $length;

            $length = ord(substr($message->data, $consumed, 1));
            $consumed += 1;

            if (strlen($message->data) - $consumed < $length) {
                return;
            }
        }

        if (strlen($message->data) - $consumed < 4) {
            return;
        }

        list($type, $class) = array_merge(unpack('n*', substr($message->data, $consumed, 4)));
        $consumed += 4;

        $message->data = substr($message->data, $consumed) ?: '';

        $message->questions[] = array(
            'name' => implode('.', $labels),
            'type' => $type,
            'class' => $class,
        );

        if ($message->header->get('qdCount') != count($message->questions)) {
            return $this->parseQuestion($message);
        }

        return $message;
    }

    private function parseAnswer(Message $message)
    {
        if (strlen($message->data) < 2) {
            return;
        }

        $consumed = 0;

        $mask = 0xc000; // 1100000000000000
        list($nameOffset) = array_merge(unpack('n', substr($message->data, $consumed, 2)));

        if ($nameOffset & $mask) {
            $consumed += 2;
            $labels[] = $message->questions[0]['name'];
            // TODO: get proper offset
        } else {
            $length = ord(substr($message->data, $consumed, 1));
            $consumed += 1;

            while ($length !== 0) {
                $labels[] = substr($message->data, $consumed, $length);
                $consumed += $length;

                $length = ord(substr($message->data, $consumed, 1));
                $consumed += 1;

                if (strlen($message->data) - $consumed < $length) {
                    return;
                }
            }
        }

        if (strlen($message->data) - $consumed < 10) {
            return;
        }

        list($type, $class) = array_merge(unpack('n*', substr($message->data, $consumed, 4)));
        $consumed += 4;

        list($ttl) = array_merge(unpack('N', substr($message->data, $consumed, 4)));
        $consumed += 4;

        list($rdLength) = array_merge(unpack('n', substr($message->data, $consumed, 2)));
        $consumed += 2;

        $rdata = null;
        if (Message::TYPE_A === $type) {
            $ip = substr($message->data, $consumed, $rdLength);
            $consumed += $rdLength;

            $rdata = inet_ntop($ip);
        }

        $message->data = substr($message->data, $consumed) ?: '';

        $name = implode('.', $labels);
        $ttl = $this->signedLongToUnsignedLong($ttl);
        $record = new Record($name, $type, $class, $ttl, $rdata);

        $message->answers[] = $record;

        return $message;
    }

    private function signedLongToUnsignedLong($i)
    {
        return $i & 0x80000000 ? $i - 0xffffffff : $i;
    }
}
