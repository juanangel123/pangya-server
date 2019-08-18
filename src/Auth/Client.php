<?php

namespace PangYa\Auth;

use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\ClientPlayer;
use PangYa\Crypt\Lib;
use PangYa\LoginServer;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Util\Util;

/**
 * This represents the global client for auth purposes.
 * TODO: this class is which should implement pooling?
 *
 * @package PangYa\Auth
 */
class Client
{
    /**
     * @var LoginServer
     */
    protected $loginServer;

    /**
     * Client constructor.
     *
     * @param  LoginServer  $loginServer
     */
    public function __construct(LoginServer $loginServer)
    {
        $this->loginServer = $loginServer;
    }

    /**
     * Execute the command.
     *
     * @param  ClientPlayer  $client
     * @param  string  $command
     * @throws BufferException
     */
    public function execute(ClientPlayer $client, string $command): void
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

            $this->parseDecryptedPacket($client, $this->loginServer->getCrypt()->decrypt(new StringBuffer($buffer->getString($size)), $client->getKey()));
        }
    }

    /**
     * Parses a decrypted packet.
     *
     * @param  ClientPlayer  $client
     * @param  PangYaBuffer  $decrypted
     * @throws BufferException
     */
    protected function parseDecryptedPacket(ClientPlayer $client, PangYaBuffer $decrypted): void
    {
        $packetType = $decrypted->getUnsignedShort();
        switch ($packetType) {
            case PacketTypes::HANDLE_PLAYER_LOGIN:
                $client->handlePlayerLogin();
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
