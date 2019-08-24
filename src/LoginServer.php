<?php

namespace PangYa;

use Exception;
use PangYa\Auth\Client;
use React\Socket\ConnectionInterface;

/**
 * Class LoginServer
 *
 * @package PangYa
 */
class LoginServer extends Server
{
    /**
     * Return the name of the server for internal purposes.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Login Server';
    }

    /**
     * Init the server.
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

            $connection->on('end', function () use ($client) {
                echo $this->getName().' - Client '.$client->getId()." has end the connection\n";
            });

            $connection->on('error', function (Exception $e) {
                echo $this->getName().' - Error: '.$e->getMessage()."\n";
            });

            $connection->on('close', function () use ($client) {
                $this->removePlayer($client);

                echo $this->getName().' - Client '.$client->getId()." has disconnected\n";
            });
        });
    }
}
