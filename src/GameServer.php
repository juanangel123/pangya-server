<?php

namespace PangYa;

use Exception;
use PangYa\Game\Client;
use React\Socket\ConnectionInterface;

/**
 * Class GameServer
 */
class GameServer extends Server
{
    /**
     * @var string
     */
    public const VERSION= '852.00';

    /**
     * Return the name of the server for internal purposes.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Game Server';
    }

    /**
     * Init the server.
     *
     * @return mixed
     */
    public function init(): void
    {
        $this->socket->on('connection', function (ConnectionInterface $connection) {
            $client = new Client($connection, $this);
            $client->connect();

            echo $this->getName().' - Client connected: '.$connection->getRemoteAddress().' - ID: '.$client->getId()."\n";

            $connection->on('data', function (string $data) use ($client) {
                $this->execute($client, $data);
            });

            $connection->on('end', function () {
                echo $this->getName()." - Client has end the connection\n";
            });

            $connection->on('error', function (Exception $e) {
                echo $this->getName().' - Error: '.$e->getMessage()."\n";
            });

            $connection->on('close', function () {
                echo $this->getName()." - Client has disconnected\n";
            });
        });
    }
}
