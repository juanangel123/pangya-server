<?php

namespace PangYa;

use Exception;
use React\Socket\ConnectionInterface;

/**
 * Class MessengerServer
 */
class MessengerServer extends Server
{
    /**
     * Return the name of the server for internal purposes.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Messenger Server';
    }

    /**
     * Init the server.
     *
     * @return mixed
     */
    public function init(): void
    {
        $this->socket->on('connection', function (ConnectionInterface $connection) {
            echo $this->getName().' - Client connected: '.$connection->getRemoteAddress()."\n";

            $connection->on('data', function (string $data) {
                dump($data);
                //$this->execute($player, $data);
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
