<?php

namespace Pangya;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use Pangya\Crypt\Lib;
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
     * The key to the authentication process.
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
        $this->key = random_int(0, 15); // Random hex.

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
        $buffer->insertInt($this->key);
        $buffer->insertArrayBytes([0x75, 0x27, 0x00, 0x00]);

        $this->connection->write($buffer->toString());
    }

    /**
     * Execute the security check for the buffer provided.
     *
     * @param  Buffer  $buffer
     * @return bool
     * @throws BufferException
     */
    public function securityCheck(Buffer $buffer): bool
    {
        $rand = $buffer->getUnsignedByte();
        echo 'Rand: '.$rand."\n";

        $posX = Lib::KEYS[($this->key << 8) + $rand + 1];
        $poxY = Lib::KEYS[($this->key << 8) + $rand + 4097];

        dump($buffer->size());
        dump($posX);
        dump($poxY);
        dump(strlen($buffer->toString()));

        $x = $buffer->setPosition($posX)->getByte();
        $y = $buffer->setPosition($poxY)->getByte();

        var_dump('x: '.$x.'\n');
        var_dump('y: '.$y.'\n');

        return false;

        //if not (y = (x xor ord(Buffer[5]))) then
        // SECURITY CHECK

    }
}
