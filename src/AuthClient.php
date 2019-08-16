<?php

namespace Pangya;

use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use Pangya\Crypt\Lib;
use Pangya\Packet\Buffer as PangyaBuffer;

/**
 * Class AuthClient
 *
 * @package Pangya
 */
class AuthClient
{

    protected const PACKET_TYPES = [
        1 => ''
    ];

    /**
     * @var Lib
     */
    protected $crypt;

    /**
     * AuthClient constructor.
     */
    public function __construct()
    {
        $this->crypt = new Lib();
    }

    /**
     * Execute the command.
     *
     * @param  Client  $client
     * @param  string  $command
     * @throws BufferException
     */
    public function execute(Client $client, string $command): void
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

            $decrypted = $this->crypt->decrypt(new StringBuffer($buffer->getString($size)), $client->getKey());

            $this->parseDecryptedPacket($decrypted);
        }

        $client->disconnect();
    }

    /**
     * Parses a decrypted packet.
     *
     * @param  PangyaBuffer  $decrypted
     * @throws BufferException
     */
    protected function parseDecryptedPacket(PangyaBuffer $decrypted): void
    {
        $packetType = $decrypted->getUnsignedShort();
        switch ($packetType) {
            case 1:
                break;
            case 3:
                break;
            case 4:
                break;
            case 6:
                break;
            case 7:
                break;
            case 8:
                break;
        }
    }
}
