<?php

namespace PangYa;

use Exception;
use Nelexa\Buffer\StringBuffer;
use PangYa\Client\AbstractClient;
use PangYa\Client\SerialId;
use PangYa\Crypt\Lib;
use React\EventLoop\LoopInterface;
use React\Socket\Server as ReactServer;

/**
 * Class Server
 *
 * @package PangYa
 */
abstract class Server
{
    /**
     * @var ReactServer
     */
    protected $socket;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * TODO: implement a player pool.
     *
     * @var AbstractClient[]
     */
    protected $players;

    /**
     * @var SerialId
     */
    protected $serialId;

    /**
     * @var Lib
     */
    protected $crypt;

    /**
     * @var bool
     */
    protected $underMaintenance = false;

    /**
     * LoginServer constructor.
     *
     * @param  string  $host
     * @param  string  $port
     * @param  LoopInterface  $loop
     */
    public function __construct(string $host, string $port, LoopInterface $loop)
    {
        $this->host = $host;
        $this->port = $port;

        $this->socket = new ReactServer($this->host.':'.$this->port, $loop);
        $this->crypt = new Lib();
        $this->serialId = new SerialId();

        $this->init();
    }

    /**
     * Return the name of the server for internal purposes.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Init the server.
     *
     * @return mixed
     */
    abstract public function init(): void;

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * @return Lib
     */
    public function getCrypt(): Lib
    {
        return $this->crypt;
    }

    /**
     * @return SerialId
     */
    public function getSerialId(): SerialId
    {
        return $this->serialId;
    }

    /**
     * @return bool
     */
    public function isUnderMaintenance(): bool
    {
        return $this->underMaintenance;
    }

    /**
     * Execute the command.
     *
     * @param  AbstractClient  $client
     * @param  string  $command
     * @throws Exception
     */
    public function execute(AbstractClient $client, string $command): void
    {
        $buffer = new StringBuffer($command);

        // Check packet size.
        if ($buffer->size() < Lib::MIN_PACKET_SIZE) {
            $client->disconnect();

            return;
        }

        // Get real packet size.
        $size = ($buffer->setPosition(1)->getUnsignedByte() + 4);
        $buffer->rewind();

        // Check and decompress all packets received.
        while ($buffer->remaining() >= $size) {
            if (!$client->securityCheck($buffer)) {
                $client->disconnect();

                return;
            }

            $client->parseDecryptedPacket($this->crypt->decrypt(new StringBuffer($buffer->getString($size)),
                $client->getKey()));
        }
    }

    // TODO: to player pool.

    /**
     * @param  AbstractClient  $client
     */
    public function addPlayer(AbstractClient $client): void
    {
        $this->players[$client->getId()] = $client;
    }

    /**
     * @param  AbstractClient  $client
     */
    public function removePlayer(AbstractClient $client): void
    {
        if (isset($this->players[$client->getId()])) {
            dump('remove player, '.$client->getId());
            unset($this->players[$client->getId()]);
        }
    }

    /**
     * @param  int  $id
     * @return AbstractClient|null
     */
    public function getPlayerById(int $id): ?AbstractClient
    {
        return $this->players[$id] ?? null;
    }

    /**
     * @param  string  $username
     * @return AbstractClient|null
     */
    public function getPlayerByUsername(string $username): ?AbstractClient
    {
        foreach ($this->players as $key => $player) {
            if ($this->players[$key]->getUsername() === $username) {
                return $this->players[$key];
            }
        }

        return null;
    }
}
