<?php

namespace Pangya;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use Pangya\Crypt\Lib;
use Pangya\Crypt\Tables;
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
     * The key related to the client.
     * This key works to:
     * - Authenticate the client.
     * - Encrypt / decrypt data.
     *
     * @var int
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
     * Return the client id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Return the key.
     *
     * @return int
     */
    public function getKey(): int
    {
        return $this->key;
    }

    /**
     * Connect to the Pangya Server using the connection.
     *
     * @throws Exception
     */
    public function connect(): void
    {
        $this->key = random_int(0, 15); // Random hex.

        // TODO: set a random connection id.
        // Original: pool between 0 to 2999 taken from an array of connections (3000).
        // $this->id = random_int(0, 2999);
        $this->id = 0;

        // TODO: add to a pool of players.

        $this->sendKey();
    }

    /**
     * Disconnect the client.
     */
    public function disconnect(): void
    {
        $this->connection->close();
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
        $buffer->insertInt($this->key << 24);
        $buffer->insertArrayBytes([0x75, 0x27, 0x00, 0x00]);

        $this->connection->write($buffer->toString());
    }

    /**
     * Execute the security check for the data provided.
     *
     * @param  Buffer  $buffer
     * @return bool
     * @throws BufferException
     */
    public function securityCheck(Buffer $buffer): bool
    {
        $rand = $buffer->getUnsignedByte();

        $x = Tables::SECURITY_CHECK_TABLE[($this->key << 8) + $rand];
        $y = Tables::SECURITY_CHECK_TABLE[($this->key << 8) + $rand + 4096];

        if ($y === ($x ^ $buffer->skip(3)->getUnsignedByte())) {
            $buffer->skip(-Lib::MIN_PACKET_SIZE);
            return true;
        }

        $buffer->skip(-Lib::MIN_PACKET_SIZE);
        return false;
    }
}
