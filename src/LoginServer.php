<?php

namespace PangYa;

use Exception;
use PangYa\AuthClient;
use React\Socket\ConnectionInterface;

/**
 * Class LoginServer
 *
 * @package PangYa
 */
class LoginServer extends Server
{
    /**
     * @var AuthClient
     */
    protected $authClient;

    /**
     * Init the server.
     */
    public function init(): void
    {
        $this->socket->on('connection', function (ConnectionInterface $connection) {
            $player = new Player($connection, $this);
            $player->connect();

            $this->authClient = new AuthClient();

            echo 'Client connected: '.$connection->getRemoteAddress().' - ID: '.$player->getId()."\n";

            $connection->on('data', function (string $data) use ($player) {
                $this->authClient->execute($player, $data);
            });

            $connection->on('end', static function () use ($player) {
                echo 'Client: '.$player->getId()." has end the connection\n";
            });

            $connection->on('error', static function (Exception $e) {
                echo 'Error: '.$e->getMessage()."\n";
            });

            $connection->on('close', function () use ($player) {
                $this->removePlayer($player);

                echo 'Client: '.$player->getId()." has disconnected\n";
            });
        });

//        $connector = new \React\Socket\Connector($this->loop);
//        $promise = $connector->connect('127.0.0.1:10110')->then(function (\React\Socket\ConnectionInterface $connection) {
//            $connection->on('data', function(string $data) {
//               echo "Client data: " . $data . "\n";
//            });
//            $connection->pipe(new \React\Stream\WritableResourceStream(STDOUT, $loop));
//            $connection->write("Hello World!\n");
//        });

//        This is not working since it is not doing both handling the connection and the input.
//        try {
//            $loop->addReadStream(fopen('php://stdin', 'rb'), static function ($stream) {
//                $line = fgets($stream);
//                if (!$line) {
//                    return;
//                }
//
//                echo 'Input: '.$line;
//            });
//        } catch (Exception $e) {
//            echo "Can't get the stdin input stream\n";
//        }
    }

    /**
     * @param  Player  $player
     */
    public function addPlayer(Player $player): void
    {
        $this->players[$player->getId()] = $player;
    }

    /**
     * @param  Player  $player
     */
    public function removePlayer(Player $player): void
    {
        if (isset($this->players[$player->getId()])) {
            unset($this->players[$player->getId()]);
        }
    }

    /**
     * @param  int  $id
     * @return Player|null
     */
    public function getPlayerById(int $id): ?Player
    {
        return $this->players[$id] ?? null;
    }
}
