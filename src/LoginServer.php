<?php

namespace PangYa;

use Exception;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Auth\PacketTypes;
use PangYa\Crypt\Lib;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Util\Util;
use React\Socket\ConnectionInterface;

/**
 * Class LoginServer
 *
 * @package PangYa
 */
class LoginServer extends Server
{
    /**
     * Init the server.
     */
    public function init(): void
    {
        $this->socket->on('connection', function (ConnectionInterface $connection) {
            $player = new Player($connection, $this);
            $player->connect();

            echo 'Client connected: '.$connection->getRemoteAddress().' - ID: '.$player->getId()."\n";

            $connection->on('data', function (string $data) use ($player) {
                $this->execute($player, $data);
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
    }

    /**
     * Execute the command.
     *
     * @param  Player  $client
     * @param  string  $command
     * @throws Exception
     */
    public function execute(Player $client, string $command): void
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

            $this->parseDecryptedPacket($client,
                $this->crypt->decrypt(new StringBuffer($buffer->getString($size)), $client->getKey()));
        }
    }

    /**
     * Parses a decrypted packet.
     *
     * @param  Player  $client
     * @param  PangYaBuffer  $decrypted
     * @throws Exception
     */
    protected function parseDecryptedPacket(Player $client, PangYaBuffer $decrypted): void
    {
        $packetType = $decrypted->getUnsignedShort();
        dump('packet type: '.$packetType);
        switch ($packetType) {
            case PacketTypes::HANDLE_PLAYER_LOGIN:
                $client->handlePlayerLogin($decrypted);
                break;
            case PacketTypes::SEND_GAME_AUTH_KEY:
                $client->sendGameAuthKey();
                break;
            case PacketTypes::HANDLE_DUPLICATE_LOGIN:
                // TODO
                break;
            case PacketTypes::CREATE_CHARACTER:
                // TODO
                break;
            case PacketTypes::NICKNAME_CHECK:
                // TODO
                break;
            case PacketTypes::REQUEST_CHARACTER_CREATE:
                $client->createCharacter($decrypted);
                break;
            case PacketTypes::GET_SERVER_LIST:
                // TODO
                dump('get server list - maybe');
                Util::showHex($decrypted);
            default:
                echo "Unknown packet:\n";
                Util::showHex($decrypted);
        }
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
