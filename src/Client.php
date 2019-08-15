<?php

namespace Pangya;

use Exception;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use React\Socket\ConnectionInterface;

/**
 * Class Client
 *
 * @package Pangya
 */
class Client
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var int Id of the connection.
     */
    protected $id = 0;

    /**
     * @var bool Flag to check if the client has been verified to send packets.
     */
    protected $verified;

    /**
     * Byte containing the key to the authentication process.
     *
     * @var string
     */
    protected $key;

    /**
     * Client constructor.
     *
     * @param  ConnectionInterface  $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the Client id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Connect to the Pangya Server using the connection.
     *
     * @throws Exception
     */
    public function connect(): void
    {
        $this->key = random_bytes(1);

        // TODO: set a random connection id.
        // Original: pool between 0 to 2999 taken from an array of connections (3000).
        // $this->id = random_int(0, 2999);
        $this->id = 0;

        // TODO: add to a pool of players.

        $this->sendKey();
    }

    /**
     * Send the key to the server.
     *
     * @throws BufferException
     */
    public function sendKey(): void
    {
        // TODO: this is not encrypted but we need to make something in the buffer to encrypt data.
        $buffer = new StringBuffer();
        $buffer->insertArrayBytes([0x00, 0x0b, 0x00, 0x00, 0x00, 0x00]);
        // TODO: to check: the key is 6 byte length (length + data).
        $buffer->insertArrayBytes([6, 0x00, 0x00, 0x00, 0x00, 0x00]);
        $buffer->insertArrayBytes([0x75, 0x27, 0x00, 0x00]);

        $this->connection->write($buffer->toString());
    }

    /**
     * @param $buffer
     */
    public function processCommand($buffer)
    {

    }
}
