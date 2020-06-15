<?php

namespace PangYa\Client;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Crypt\Lib;
use PangYa\Crypt\Tables;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Server;
use React\Socket\ConnectionInterface;

/**
 * Class AbstractClient
 *
 * @package PangYa\Client
 */
abstract class AbstractClient
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var int Id of the connection.
     */
    protected $id = 0;

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
     * Auth key used for the login process.
     *
     * @var string
     */
    protected $loginAuthKey;

    /**
     * Auth key used for the game.
     *
     * @var string
     */
    protected $gameAuthKey;

    /**
     * @var string
     */
    protected $username = '';

    /**
     * Client constructor.
     *
     * @param  ConnectionInterface  $connection
     * @param  Server  $server
     */
    public function __construct(ConnectionInterface $connection, Server $server)
    {
        $this->connection = $connection;
        $this->server = $server;
    }

    /**
     * Send the key to the server.
     *
     * @throws BufferException
     */
    abstract public function sendKey(): void;

    /**
     * @param  PangYaBuffer  $decrypted
     */
    abstract public function parseDecryptedPacket(PangYaBuffer $decrypted): void;

    /**
     * Handle player login.
     *
     * @param  PangYaBuffer  $buffer
     * @return bool
     * @throws Exception
     */
    abstract public function handlePlayerLogin(PangYaBuffer $buffer): bool;

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
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param  string  $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * Connect to the PangYa Server using the connection.
     *
     * @throws Exception
     */
    public function connect(): void
    {
        $this->id = $this->server->getSerialId()->getId();
        $this->key = random_int(0, 15); // Random hex.

        $this->server->addPlayer($this);

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
     * Send raw data to the connection.
     *
     * @param  string  $data
     * @throws BufferException
     */
    public function sendRawData(string $data): void
    {
        $this->connection->write(new StringBuffer($data));
    }

    /**
     * Send buffer data through the connection.
     *
     * @param  Buffer  $buffer
     * @param  bool  $encrypt
     * @throws BufferException
     */
    protected function send(Buffer $buffer, bool $encrypt = true): void
    {
        if (!$buffer->size()) {
            return;
        }

        if ($encrypt) {
            $buffer = $this->server->getCrypt()->encrypt($buffer, $this->key);
        }

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
