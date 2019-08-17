<?php

namespace Pangya;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use Pangya\Auth\Client;
use Pangya\Crypt\Lib;
use Pangya\Crypt\Tables;
use React\Socket\ConnectionInterface;

/**
 * This class represents the client for player related purposes.
 *
 * @package Pangya
 */
class ClientPlayer
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var LoginServer
     */
    protected $loginServer;

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
     * ClientPlayer constructor.
     *
     * @param  ConnectionInterface  $connection
     * @param  LoginServer  $loginServer
     */
    public function __construct(ConnectionInterface $connection, LoginServer $loginServer)
    {
        $this->connection = $connection;
        $this->loginServer = $loginServer;
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
     * Send buffer data through the connection.
     *
     * @param  Buffer  $buffer
     * @param  bool  $encrypt
     */
    protected function send(Buffer $buffer, bool $encrypt = false): void
    {
        if (!$buffer->size()) {
            return;
        }

        if ($encrypt) {
            $buffer = $this->loginServer->getCrypt()->encrypt($buffer, $this->key);
        }

        $this->connection->write($buffer->toString());
    }

    /**
     * Send the key to the server.
     *
     * @throws BufferException
     */
    public function sendKey(): void
    {
        $buffer = new StringBuffer();
        $buffer->insertArrayBytes([0x00, 0x0b, 0x00, 0x00, 0x00, 0x00]);
        $buffer->insertInt($this->key << 24);
        $buffer->insertArrayBytes([0x75, 0x27, 0x00, 0x00]);

        $this->send($buffer);
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

    /**
     * @throws BufferException
     */
    public function handlePlayerLogin(): void
    {
        //if ($this->loginServer->isUnderMaintenance()) {
        if (true) {
            $this->send(new StringBuffer([0x01, 0x00, 0xe3, 0x48, 0xd2, 0x4d, 0x00]), true);
        }
    }
}
