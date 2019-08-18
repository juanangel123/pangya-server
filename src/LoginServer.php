<?php

namespace PangYa;

use Exception;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Auth\Client;
use PangYa\Crypt\Lib;
use PangYa\Util\MiniLZO;
use PangYa\Util\Util;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

/**
 * Class LoginServer
 *
 * @package PangYa
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

        // $this->testEncryption();
    }

    /**
     * @throws BufferException
     */
    protected function testEncryption(): void
    {
        $buffer = new StringBuffer();
        $buffer->insertArrayBytes([0x01, 0x00, 0xe3, 0x48, 0xd2, 0x4d, 0x00]);
        $compressed = MiniLZO::compress1X1($buffer->rewind()->getArrayBytes($buffer->size()));


        $buffer2 = new StringBuffer();
        $buffer2->insertArrayBytes($compressed);
        Util::showHex($buffer2);
        // 18 1 0 E3 48 D2 4D 0 11 0 0

        $buffer3 = $this->crypt->encrypt($buffer, 0, 0);
        Util::showHex($buffer3);
        // 0 10 0 0 0 0 0 7 18 1 0 E4 50 D3 4D E3 59 D2 4D
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
