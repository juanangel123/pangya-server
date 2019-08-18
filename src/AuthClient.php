<?php

namespace PangYa\Auth;

use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Player;
use PangYa\Crypt\Lib;
use PangYa\LoginServer;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Server;
use PangYa\Util\Util;

/**
 * This represents the client for auth purposes.
 *
 * @package PangYa
 */
class AuthClient extends Server
{
    /**
     *
     */
    public function init(): void
    {

    }

    /**
     * Execute the command.
     *
     * @param  Player  $client
     * @param  string  $command
     * @throws BufferException
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

            $this->parseDecryptedPacket($client, $this->getCrypt()->decrypt(new StringBuffer($buffer->getString($size)), $client->getKey()));
        }
    }

    /**
     * Parses a decrypted packet.
     *
     * @param  Player  $client
     * @param  PangYaBuffer  $decrypted
     * @throws BufferException
     */
    protected function parseDecryptedPacket(Player $client, PangYaBuffer $decrypted): void
    {
        $packetType = $decrypted->getUnsignedShort();
        switch ($packetType) {
            case PacketTypes::HANDLE_PLAYER_LOGIN:
                $client->handlePlayerLogin($decrypted);
                break;
            case PacketTypes::SEND_GAME_AUTH_KEY:
                break;
            case PacketTypes::HANDLE_DUPLICATE_LOGIN:
                break;
            case PacketTypes::CREATE_CHARACTER:
                break;
            case PacketTypes::NICKNAME_CHECK:
                break;
            case PacketTypes::REQUEST_CHARACTER_CREATE:
                break;
            default:
                echo "Unknown packet:\n";
                Util::showHex($decrypted);
        }
    }
}
