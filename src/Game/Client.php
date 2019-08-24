<?php

namespace PangYa\Game;

use Exception;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Client\AbstractClient;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Util\Util;

/**
 * Class Client
 *
 * @package PangYa\Game
 */
class Client extends AbstractClient
{
    /**
     * Send the key to the server.
     *
     * @throws BufferException
     */
    public function sendKey(): void
    {
        $buffer = new StringBuffer();
        $buffer->insertArrayBytes([0x00, 0x06]);
        $buffer->insertArrayBytes([0x00, 0x00]);
        $buffer->insertArrayBytes([0x3f, 0x00, 0x01, 0x01]);
        $buffer->insertByte($this->key);

        $this->send($buffer, false);
    }

    /**
     * @param  PangYaBuffer  $decrypted
     *
     * @throws BufferException
     * @throws Exception
     */
    public function parseDecryptedPacket(PangYaBuffer $decrypted): void
    {
        $packetType = $decrypted->getUnsignedShort();
        dump('login server packet type: '.$packetType);
        switch ($packetType) {
            case PacketTypes::PLAYER_LOGIN:
                $this->handlePlayerLogin($decrypted);
                break;
            default:
                echo "Unknown packet:\n";
                Util::showHex($decrypted);
                break;
        }
    }

    /**
     * Handle player login.
     *
     * @param  PangYaBuffer  $buffer
     * @return bool
     * @throws Exception
     */
    public function handlePlayerLogin(PangYaBuffer $buffer): bool
    {
        $id = $buffer->readPString();
        $uid = $buffer->getInt();

        $buffer->skip(6);

        $code1 = $buffer->readPString();
        $version = $buffer->readPString();

        $buffer->skip(8);

        $code2 = $buffer->readPString();

        // TODO: get player by UID.
        $client = null;

        if (!$client) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x76, 0x02, 0x2d, 0x01, 0x00, 0x00]); // Code 300.
            $this->disconnect();

            dump('paso bien');

            return false;
        }

        return true;
    }
}
