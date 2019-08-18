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

        //$this->testEncryption();
    }

    /**
     * Test against server encryption / decryption and MiniLZO compression / decompression.
     *
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

        $decompressed = MiniLZO::decompress1X($compressed);

        $buffer4 = new StringBuffer();
        $buffer4->insertArrayBytes($decompressed);
        Util::showHex($buffer4);
        // 1 0 E3 48 D2 4D 0

        $buffer5 = new StringBuffer();
        $buffer5->insertArrayBytes([
            0xcc,
            0x3c,
            0x00,
            0x00,
            0x8e,
            0x01,
            0x00,
            0x04,
            0x7d,
            0x75,
            0x65,
            0x77,
            0x74,
            0x54,
            0x65,
            0x43,
            0x4d,
            0x18,
            0x46,
            0x06,
            0x7b,
            0x7b,
            0x02,
            0x02,
            0x74,
            0x71,
            0x75,
            0x70,
            0x05,
            0x05,
            0x02,
            0x07,
            0x72,
            0x73,
            0x76,
            0x77,
            0x04,
            0x7c,
            0x76,
            0x06,
            0x73,
            0x0a,
            0x04,
            0x70,
            0x02,
            0x74,
            0x01,
            0x42,
            0x34,
            0x46,
            0x36,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
        ]);
        $buffer5->rewind();

        $result = $this->crypt->decrypt($buffer5, 1);
        Util::showHex($result);
        // KEY: 1
        // 01 00 04 00 74 65 73 74 20 00 30 39 38 46 36 42 43 44 34 36 32 31 44 33 37 33 43 41 44 45 34 45 38 33 32 36 32 37 42 34 46 36 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
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
