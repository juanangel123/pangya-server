<?php

namespace PangYa\Game;

use Exception;
use Nelexa\Buffer\BufferException;
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
        $buffer = new PangYaBuffer();
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
        dump('game server packet type: '.$packetType);
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
        if ( ! $client) {
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
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd3, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x01, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x03, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x1c, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x1e, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x20, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x05, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x08, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x0b, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x0, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x12, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
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

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x12, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x15, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x0e, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x14, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x16, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x18, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x1a, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x44, 0x00, 0xd2, 0x22, 0x00, 0x00, 0x00]);
        $this->send($response);

        $this->sendStatistics();

        // TODO: Card.

        // TODO: Character data.
        $this->sendCharacterData();

        // TODO: Caddie data.
        $this->sendCaddieData();

        // TODO: Items.
        $this->sendItems();

        $this->sendLobbyLists();

        // Junk.
        $response = new PangYaBuffer();
        $response->insertArrayBytes(JunkData::PLAYER_INFO_1);
        $this->send($response);

        // Send achievement.
        $this->sendAchievement();

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0xf1, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x35, 0x00]);
        $this->send($response);

        // Cards.
        $this->sendCards();

        // Cookie.
        $this->sendCookie();

        // Junk.
        $response = new PangYaBuffer();
        $response->insertArrayBytes(JunkData::PLAYER_INFO_2);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes(JunkData::PLAYER_INFO_3);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0xb4, 0x00, 0x05, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0xb4, 0x00, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes(JunkData::PLAYER_INFO_4);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x5d, 0x02, 0x05, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes(JunkData::PLAYER_INFO_5);
        $this->send($response);
        // End junk.

        $this->showMailPopup();
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
        $response->insertString($this->username.'1234', 16); // Nickname.
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
        $response->insertString($this->username.'1234', 22); // Guild leader nickname.

        $this->send($response);
    }

    /**
     * @throws BufferException
     */
    protected function sendCharacterData(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x70, 0x00]);
        // Character count.
        // TODO: two times?
        $response->insertShort(0);
        $response->insertShort(0);

        // TODO: character data.

        $this->send($response);
    }

    /**
     * @throws BufferException
     */
    protected function sendCaddieData(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x71, 0x00]);
        // Caddie count.
        // TODO: two times?
        $response->insertShort(0);
        $response->insertShort(0);

        // TODO: caddie data.

        $this->send($response);
    }

    /**
     * @throws BufferException
     */
    protected function sendItems(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x73, 0x00]);
        // Items count.
        // TODO: two times?
        $response->insertShort(1); // + 1
        $response->insertShort(1);

        // TODO: items data.

        // Tiki.
        $response->insertArrayBytes([
            0xf4,
            0xb4,
            0x0d,
            0x46,
            0x42,
            0x00,
            0x00,
            0x1a,
            0x18,
            0x00,
            0x00,
            0x00,
            0x01,
            0x00,
            0xa4,
            0x00,
            0x17,
            0x02,
            0x00,
            0x00,
            0x00,
            0x00,
            0x00,
            0x20,
            0xf0,
            0xe1,
            0x78,
            0x59,
            0x00,
            0x00,
            0x00,
            0x00,
            0x80,
            0x33,
            0x7a,
            0x59,
            0x00,
            0x00,
            0x00,
            0x00,
            0x02,
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

        // Mascots.
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0xe1, 0x00]);
        $response->insertByte(0);

        // TODO: mascots data.
        $this->send($response);

        // Toolbars.

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x72, 0x00]);
        $response->insertInt(0); // Caddie.
        $response->insertInt(0); // Character ID.
        $response->insertInt(0); // Club ID.
        $response->insertInt(0); // Ball ID.
        $response->insertInt(0); // Item slot 1.
        $response->insertInt(0); // Item slot 2.
        $response->insertInt(0); // Item slot 3.
        $response->insertInt(0); // Item slot 4.
        $response->insertInt(0); // Item slot 5.
        $response->insertInt(0); // Item slot 6.
        $response->insertInt(0); // Item slot 7.
        $response->insertInt(0); // Item slot 8.
        $response->insertInt(0); // Item slot 9.
        $response->insertInt(0); // Item slot 10.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Title IDX.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Unknown.
        $response->insertInt(0); // Title Type ID.
        $response->insertInt(0); // Mascot index.
        $response->insertInt(0); // Poster left.
        $response->insertInt(0); // Poster right.
        $this->send($response);
        // TODO: Item slot.
    }

    /**
     * @throws BufferException
     */
    public function sendLobbyLists(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x4d, 0x00]);
        $response->insertByte(0); // Count.

        // TODO: build lobbies.

        $this->send($response);
    }

    /**
     * @throws BufferException
     */
    public function sendAchievement(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x1d, 0x02]);
        $response->insertInt(0);
        // Achievement counter count (double).
        $response->insertInt(0);
        $response->insertInt(0);

        // TODO: Achievement counters.

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x1e, 0x02]);
        $response->insertInt(0);
        // Achievement count (double).
        $response->insertInt(0);
        $response->insertInt(0);
        // TODO: achievements.
    }

    /**
     * @throws BufferException
     */
    public function sendCards(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x38, 0x01, 0x00, 0x00, 0x00, 0x00]);
        $response->insertShort(0); // Count.

        // TODO: Cards.

        $this->send($response);

        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x36, 0x01]);
        $this->send($response);
        // TODO: CARD SPCL NOT YET IMPLEMENTED
    }

    /**
     * @throws BufferException
     */
    public function sendCookie(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x96, 0x00]);
        $response->insertInt(1000); // Cookie amount.
        $response->insertString('', 4);
        $this->send($response);
    }

    /**
     * @throws BufferException
     */
    public function showMailPopup(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x10, 0x02, 0x00, 0x00, 0x00, 0x00]);
        $response->insertInt(0); // Count.

        // TODO: mail.
    }

    /**
     * @throws BufferException
     */
    protected function sendDisconnectResponse(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x76, 0x02, 0x2d, 0x01, 0x00, 0x00]); // Code 300.
        $this->send($response);
        $this->disconnect();
    }
}
