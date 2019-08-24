<?php

namespace PangYa\Game;

use Exception;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Client\AbstractClient;
use PangYa\GameServer;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Packet\JunkData;
use PangYa\Server;
use PangYa\Util\Util;
use React\Socket\ConnectionInterface;

/**
 * Class Client
 *
 * @package PangYa\Game
 */
class Client extends AbstractClient
{
    /**
     * @var Player
     */
    protected $player;

    /**
     * Client constructor.
     *
     * @param  ConnectionInterface  $connection
     * @param  Server  $server
     */
    public function __construct(ConnectionInterface $connection, Server $server)
    {
        parent::__construct($connection, $server);

        $this->player = new Player();
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

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

        dump('game send key');
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
        $username = $buffer->readPString();
        $id = $buffer->getInt();

        $buffer->skip(6);

        $code1 = $buffer->readPString();
        $version = $buffer->readPString();

        $buffer->skip(8);

        $code2 = $buffer->readPString();

        $client = $this->server->getPlayerById($id);
        if (!$client) {
            $this->sendDisconnectResponse();

            return false;
        }

        // Set user data:
        // - Username
        $client->setUsername($username);
        // - Nickname
        // - Sex
        // - Capabilities
        // - UID
        // - Cookie
        // - Locker pang
        // - Locker pwd

        // - Auth key login
        // - Auth key server

        // TODO: set verified.

        $this->sendPlayerInfo();

        return true;
    }

    /**
     * Send player info.
     *
     * @throws BufferException
     */
    protected function sendPlayerInfo(): void
    {
        // Don't know why.

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd3, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x01, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x03, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x1c, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x1e, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x20, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x05, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x08, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x0b, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x0, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x12, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([
            0x1f,
            0x01,
            0x03,
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
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x12, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x15, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x0e, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x14, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x16, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x18, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x1a, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new StringBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x22, 0x00, 0x00, 0x00]);
        $this->send($response);

        $this->sendStatistics();
    }

    /**
     * @throws BufferException
     */
    protected function sendStatistics(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0x00, 0x06, 0x00]);
        $response->insertString(GameServer::VERSION, 6);
        $response->insertArrayBytes([0xff, 0xff]);
        $response->insertString($this->username, 15);
        $response->insertString('', 7);
        $response->insertString($this->username.'(e32)', 16); // Nickname.
        $response->insertString('', 6);
        $response->insertString('Guild name', 21);
        $response->insertString('', 9); // Guild image.
        $response->insertString('', 7);
        $response->insertInt(0); // Capabilities.
        $response->insertInt(0);
        $response->insertInt(0); // Connection ID.
        $response->insertString('', 12);
        $response->insertInt(0); // Guild ID.

        $response->insertArrayBytes([
            0x00,
            0x00,
            0x00,
            0x00,
            0x80,
            0x00,
            0xff,
            0xff,
            0xff,
            0xff,
            0xff,
            0xff,
        ]);
        $response->insertString('', 16);

        $response->insertString($this->username.'@NT', 18); // ??
        $response->insertString('', 110);
        $response->insertInt(0); // UID

        $response->insertByte($this->player->getStatistics()->drive);

        $response->insertArrayBytes(JunkData::LOGIN);

        $response->insertArrayBytes(Util::getGameTime());

        $response->insertArrayBytes([0x02, 0x00, 0x00, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0x00, 0x00, 0x00, 0x00]);

        $response->insertArrayBytes([0x00, 0x00, 0x00]);
        $response->insertArrayBytes([0x00]);
        $response->insertArrayBytes([0x00, 0x00, 0x00, 0x00]);

        $response->insertArrayBytes([0x01, 0x00, 0x00, 0x00, 0x00]);
        $response->insertByte(8); // Grand Prix. 0 for normal.
        $response->insertArrayBytes([0x00, 0x00]);

        $response->insertInt(0); // Guild ID.
        $response->insertString('Guild name', 20);
        $response->insertString('', 9);

        $response->insertInt(1); // Guild total member.
        $response->insertString('', 9); // Guild image.
        $response->insertString('', 3);

        $response->insertString('Guild notice', 101);
        $response->insertString('Guild introducing', 101);
        $response->insertInt(1); // Guild position.
        $response->insertInt($this->id); // Guild leader uid.
        $response->insertString($this->getUsername().'(e32)', 22); // Guild leader nickname.

        $this->send($response);
    }

    /**
     * @throws BufferException
     */
    protected function sendDisconnectResponse(): void
    {
        $response = new StringBuffer();
        $response->insertArrayBytes([0x76, 0x02, 0x2d, 0x01, 0x00, 0x00]); // Code 300.
        $this->send($response);
        $this->disconnect();
    }
}
