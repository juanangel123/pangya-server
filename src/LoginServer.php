<?php

namespace Pangya;

use Exception;
use Pangya\Auth\Client;
use Pangya\Crypt\Lib;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

/**
 * Class LoginServer
 *
 * @package Pangya
 */
class LoginServer
{
    /**
     * @var Server
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
     * @var Client;
     */
    protected $authClient;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

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

        $this->socket = new Server($host.':'.$port, $this->loop);
        $this->socket->on('connection', function (ConnectionInterface $connection) {
            $client = new ClientPlayer($connection, $this);
            $client->connect();

            echo 'Client connected: '.$connection->getRemoteAddress().' - ID: '.$client->getId()."\n";

            $connection->on('data', function (string $data) use ($client) {
                $this->authClient->execute($client, $data);
            });

            $connection->on('end', static function () use ($client) {
                echo 'Client: '.$client->getId()." has end the connection\n";
            });

            $connection->on('error', static function (Exception $e) {
                echo 'Error: '.$e->getMessage()."\n";
            });

            $connection->on('close', static function () use ($client) {
                echo 'Client: '.$client->getId()." has disconnected\n";
            });
        });

        // This is not working since it is not doing both handling the connection and the input.
        //try {
        //    $loop->addReadStream(fopen('php://stdin', 'rb'), static function ($stream) {
        //        $line = fgets($stream);
        //        if (!$line) {
        //            return;
        //        }
        //
        //        echo 'Input: '.$line;
        //    });
        //} catch (Exception $e) {
        //    echo "Can't get the stdin input stream\n";
        //}

        $this->crypt = new Lib();
        $this->authClient = new Client($this);
    }

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
