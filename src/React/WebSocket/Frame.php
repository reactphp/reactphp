<?php
/**
 * This file defines the Frame class
 */
namespace React\WebSocket;

/**
 * ASCII-art representation of a websocket frame, from RFC 6455
 *
 * @see http://tools.ietf.org/html/rfc6455 Page 28
 *
 *  0                   1                   2                   3
 *  0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 * +-+-+-+-+-------+-+-------------+-------------------------------+
 * |F|R|R|R| opcode|M| Payload len |    Extended payload length    |
 * |I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
 * |N|V|V|V|       |S|             |   (if payload len==126/127)   |
 * | |1|2|3|       |K|             |                               |
 * +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
 * |     Extended payload length continued, if payload len == 127  |
 * + - - - - - - - - - - - - - - - +-------------------------------+
 * |                               |Masking-key, if MASK set to 1  |
 * +-------------------------------+-------------------------------+
 * | Masking-key (continued)       |          Payload Data         |
 * +-------------------------------- - - - - - - - - - - - - - - - +
 * :                     Payload Data continued ...                :
 * + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
 * |                     Payload Data continued ...                |
 * +---------------------------------------------------------------+
 */

/**
 * Representation of a WebSocket Frame
 */
class Frame {
    /**
     * Currently known opcodes.
     * 
     * These are carried by the last 4 bits of the first byte in a websocket frame.
     */
    const OPCODE_CONTINUATION = 0;
    const OPCODE_TEXT = 1;
    const OPCODE_BINARY = 2;
    const OPCODE_CLOSE = 8;
    const OPCODE_PING = 9;
    const OPCODE_PONG = 10;

    /**
     * Signifies that no further frames follow this frame.
     *
     * @var bool
     */
    protected $fin;

    /**
     * An opcode as defined in RFC6455, expected to be one of the OPCODE_* constants. 4 bits.
     *
     * @var int
     */
    protected $opcode;

    /**
     * The masking key. A 32-bit integer.
     *
     * @var int
     */
    protected $mask;

    /**
     * The payload length. 6, 16, or 64 bits.
     *
     * @var int
     */
    protected $length;

    /**
     * The payload. Binary data.
     *
     * @var string
     */
    protected $payload;

    /**
     * Unpack a websocket frame from a buffer.
     *
     * @param string $buf The binary buffer that should contain a websocket frame.
     * @param int $buflen The length of the buffer.
     * @return Frame The unpacked frame, or false if a frame could not be unpacked from the given buffer.
     */
    public static function unpack(&$buf, &$buflen) {
        /* We need at least 2 bytes. */
        if ($buflen < 2) {
            return FALSE;
        }

        /* The first two bytes define flags, opcode, mask (bool), and initial length. */
        list($flags, $len) = array_values(unpack("C2", $buf));
        $fin = (bool)($flags & 0x80); /* Fin is the top-bit at 0x80, or binary mask 10000000 */
        $opcode = $flags & 0x0f; /* The opcode is the 4 bits at 0x0f, or binary mask 00001111 */
        $mask = (bool)($len & 0x80); /* Mask is the top bit at 0x80, or binary mask 10000000 */
        $len = $len & 0x7f; /* Length is the bottom 7 bits, or 0x7f, or binary mask 01111111 */

        /* From here we can determine the overall frame length, and determine if we have enough buffer for it. */
        $key = NULL;
        if ($len == 127) {
            $headerlen = 10 + ($mask ? 4 : 0); /* header + length + mask */

            if ($buflen < $headerlen) {
                return FALSE; /* Not enough data yet. */
            }

            if ($mask) {
                list(/*ign*/, $len1, $len2, $key) = array_values(unpack("na/N3b", $buf));
            } else {
                list(/*ign*/, $len1, $len2) = array_values(unpack("na/N2b", $buf));
            }

            $len = ($len1 << 32) | $len2;
        } elseif ($len == 126) {
            $headerlen = 4 + ($mask ? 4 : 0); /* header + length + mask */

            /* The following 2 bytes are a 16-bit unsigned short length (up to 64k) */
            if ($buflen < $headerlen) { /* buffer has to be at least header+length+mask long to proceed. */
                return FALSE; /* Not enough data yet. */
            }

            if ($mask) {
                list(/*ign*/, $len, $key) = array_values(unpack("n2a/Nb", $buf));
            } else {
                list(/*ign*/, $len) = array_values(unpack("n2", $buf));
            }
        } else {
            $headerlen = 2 + ($mask ? 4 : 0); /* header + length + mask */

            if ($buflen < $headerlen) {
                return FALSE; /* Not enough data yet. */
            }

            if ($mask) {
                list(/*ign*/, $key) = array_values(unpack("na/Nb", $buf));
            }
        }

        if ($buflen < ($headerlen + $len)) {
            return FALSE; /* Not enough data for a complete frame. */
        }

        $payload = substr($buf, $headerlen, $len);

        /* If masked, (un)mask the data (slow) */
        if ($mask) {
            self::mask($payload, $len, $key);
        }

        /* We've successfully parsed and decoded the frame data, update the buffer. */
        $buflen -= ($headerlen + $len);
        $buf = $buflen ? substr($buf, $headerlen + $len) : "";

        return new Frame($fin, $opcode, $key, $len, $payload);
    }

    /**
     * Pack the frame into a binary buffer
     *
     * @return string The frame object, encoded to a binary string according to the RFC.
     */
    public function pack() {
        /* opcode and Fin bit. */
        $opcode = $this->opcode; 
        if ($this->fin) {
            $opcode |= (1 << 7);
        }

        /* length and mask bit. */
        $length = $this->mask ? (1 << 7) : 0;

        /* Add one of the three length encodings to the buffer */
        if ($this->len <= 125) {
            $length |= $this->len;
            $buf = pack("C2", $opcode, $length);
            $buflen = 2;
        } elseif ($this->len <= 65535) {
            $length |= 126;
            $buf = pack("C2n", $opcode, $length, $this->len);
            $buflen = 4;
        } else {
            $length |= 127;
            $buf = pack("C2N2", $opcode, $length, $this->len >> 32, $this->len & 0xffffffff);
            $buflen = 10;
        }

        /* Possibly add the mask to the buffer and mask the payload */
        $payload = $this->payload;
        if ($this->mask) {
            $buf .= pack("N", $this->getMask());
            $buflen += 4;
            self::mask($payload, $this->len, $this->mask);
        }

        /* Add the (possibly masked) payload to the buffer */
        $buf .= $payload;
        $buflen += $this->len;

        return array($buf, $buflen);
    }

    public function __construct($fin, $opcode, $mask, $len, $payload)
    {
        $this->fin = (bool)$fin;
        $this->opcode = ((int)$opcode) & 0x0f;
        $this->setMask($mask);
        $this->len = $len;
        $this->payload = $payload;
    }

    /**
     * Returns true if this is the last frame in a sequence.
     *
     * @return bool
     */
    public function isFin()
    {
        return $this->fin;
    }

    /**
     * Returns the opcode for this frame.
     *
     * @return int
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * Is this frame masked?
     *
     * @return bool If true this frame is/was/will be masked
     */
    public function isMasked()
    {
        return !empty($this->mask);
    }

    /**
     * Get the frame mask.
     *
     * @return int The 32-bit frame mask, or null if no mask is to be used.
     */
    public function getMask()
    {
        /* If the mask was specified as a boolean, generate a random mask. */
        if ($this->mask === TRUE) {
            $this->mask = mt_rand(0, 2147483647) + mt_rand(1, 2147483647); /* Though cryptographically probably slightly worse, we want at least 1 because anything XOR 0 is itself. */
        }
        return $this->mask;
    }

    public function setMask($mask) {
        if (is_int($mask)) {
            $this->mask = $mask & 0xffffffff;
        } elseif ($mask === TRUE) {
            $this->mask = TRUE; /* Mask will be generated later. */
        } else {
            $this->mask = NULL; /* No mask. */
        }
    }

    /**
     * Get the payload length.
     *
     * @return int
     */
    public function getLength()
    {
        return $this->len;
    }

    /**
     * Get the frame payload
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /** 
     * Apply a mask to a binary buffer.
     *
     * @param string &$buf The buffer to be masked. The buffer is masked in-place.
     * @param int $buflen The length of the buffer.
     * @param int $key The 32-bit masking key.
     */
    public static function mask(&$buf, $buflen, $key)
    {
        $keybytes = array(
            ($key & 0xff000000) >> 24,
            ($key & 0x00ff0000) >> 16,
            ($key & 0x0000ff00) >> 8,
            $key & 0x000000ff
        );

        for ($i = 0, $j = 0; $i < $buflen; $i++, $j++) {
            if ($j > 3) $j = 0;

            $buf[$i] = chr(ord($buf[$i]) ^ $keybytes[$j]);
        }
    }
}
