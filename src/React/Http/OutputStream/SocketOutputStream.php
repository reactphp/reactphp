<?php

namespace React\Http\OutputStream;

use React\Socket\Connection;
use Symfony\Component\HttpFoundation\OutputStream\OutputStreamInterface;

/**
 * OutputStream for Socket Connection
 */
class SocketOutputStream implements OutputStreamInterface
{
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        if ($this->conn->isOpen()) {
            $this->conn->write($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->conn->close($data);
    }
}
