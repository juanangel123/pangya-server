<?php

namespace PangYa;

use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Crypt\Lib;
use PangYa\Util\MiniLZO;
use PangYa\Util\Util;
use React\EventLoop\Factory;
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
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Lib
     */
    protected $crypt;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var Player[]
     */
    protected $players;

    /**
     * @var bool
     */
    protected $underMaintenance = false;

    /**
     * LoginServer constructor.
     *
     * @param  string  $host
     * @param  string  $port
     */
    public function __construct(string $host, string $port)
    {
        $this->loop = Factory::create();
        $this->host = $host;
        $this->port = $port;

        $this->socket = new ReactServer($this->host.':'.$this->port, $this->loop);
        $this->crypt = new Lib();

        $this->init();
    }

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
     * @return LoopInterface
     */
    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    /**
     * @return Lib
     */
    public function getCrypt(): Lib
    {
        return $this->crypt;
    }

    /**
     * @return bool
     */
    public function isUnderMaintenance(): bool
    {
        return $this->underMaintenance;
    }
}
