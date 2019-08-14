<?php

namespace Pangya;

use Exception;
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
     * Byte.
     *
     * @var string
     */
    protected $key;

    /**
     * @var int Id of the connection.
     */
    protected $id = 0;

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
     */
    public function sendKey(): void
    {
        // TODO: pangya buffer create.
        // TODO: this is not encrypted but we need to make something in the buffer to encrypt data.
        // TODO: to check: the key is 6 byte length? (length + data)
        $this->connection->write(pack('C*', ...[0, 11, 0, 0, 0, 0, 6, 0, 0, 0, 0, 0, 0, 117, 39, 0, 0]));
    }
}
