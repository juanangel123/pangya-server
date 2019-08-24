<?php

namespace PangYa;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Auth\PacketTypes;
use PangYa\Game\Client;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Util\Util;

/**
 * This class represents the player.
 * TODO: change to auth client.
 *
 * @package PangYa
 */
class Player extends Client
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $nickname;

    /**
     * @var bool Flag to check if the client has been verified to send packets.
     */
    protected $verified;

    /**
     * Auth key used for the login process.
     *
     * @var string
     */
    protected $authKeyLogin;

    /**
     * Auth key used for the game.
     *
     * @var string
     */
    protected $authKeyGame;

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param  string  $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getNickname(): string
    {
        return $this->nickname;
    }

    /**
     * @param  string  $nickname
     */
    public function setNickname(string $nickname): void
    {
        $this->nickname = $nickname;
    }

    /**
     * Send the key to the server.
     *
     * @throws BufferException
     */
    public function sendKey(): void
    {
        $buffer = new StringBuffer();
        $buffer->insertArrayBytes([0x00, 0x0b, 0x00, 0x00, 0x00, 0x00]);
        $buffer->insertInt($this->key << 24);
        $buffer->insertArrayBytes([0x75, 0x27, 0x00, 0x00]);

        $this->send($buffer, false);
    }

    /**
     * Parse a decrypted packet.
     *
     * @param  PangYaBuffer  $decrypted
     * @throws Exception
     */
    public function parseDecryptedPacket(PangYaBuffer $decrypted): void
    {
        $packetType = $decrypted->getUnsignedShort();
        dump('login server packet type: '.$packetType);
        switch ($packetType) {
            case PacketTypes::HANDLE_PLAYER_LOGIN:
                $this->handlePlayerLogin($decrypted);
                break;
            case PacketTypes::SEND_GAME_AUTH_KEY:
                $this->sendGameAuthKey();
                break;
            case PacketTypes::HANDLE_DUPLICATE_LOGIN:
                dump('handle duplicate login');
                break;
            case PacketTypes::CREATE_CHARACTER:
                dump('create character');
                break;
            case PacketTypes::NICKNAME_CHECK:
                dump('nickname check');
                break;
            case PacketTypes::REQUEST_CHARACTER_CREATE:
                $this->createCharacter($decrypted);
                break;
            case PacketTypes::GET_SERVER_LIST:
                dump('get server list - maybe');
                Util::showHex($decrypted);
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
        if ($this->server->isUnderMaintenance()) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xe3, 0x48, 0xd2]);
            $response->insertString(Messages::MAINTENANCE);
            $response->insertByte(0x00);

            $this->send($response);
        }

        if ((!$user = $buffer->readPString()) || (!$password = $buffer->readPString())) {
            return false;
        }

        dump('user: '.$user);
        dump('password: '.$password);

        // Set auth.
        $this->authKeyLogin = Util::randomAuth(7);
        $this->authKeyGame = Util::randomAuth(7);

        if ($this->server->getPlayerById($this->id)) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xe3, 0x4b, 0xd2]);
            $response->insertString(Messages::PLAYER_ALREADY_LOGGED);
            $response->insertByte(0x00);
            $this->send($response);

            return false;
        }

        // TODO: User not found.
        if (false) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xe3, 0x6f, 0xd2]);
            $response->insertString(Messages::PLAYER_NOT_FOUND);
            $response->insertByte(0x00);
            $this->send($response);

            return false;
        }

        // TODO: Password error.
        if (false) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xe3, 0x5b, 0xd2]);
            $response->insertString(Messages::PLAYER_PASSWORD_ERROR);
            $response->insertByte(0x00);
            $this->send($response);

            return false;
        }

        // TODO: Player banned.
        if (false) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xe3, 0xf4, 0xd1]);
            $response->insertString(Messages::PLAYER_BANNED);
            $response->insertByte(0x00);
            $this->send($response);

            return false;
        }

        // TODO:
        // - Set username.
        $this->setUsername('test1234');
        // - Set first set.
        // - Set UID?
        // - Set nickname.
        $this->setNickname('test1234(e32)');
        // - Set verified.

        // TODO: Logon?
        if (false) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xe3, 0xf3, 0xd1]);
            $response->insertString('Logon?');
            $response->insertByte(0x00);
            $this->send($response);

            return false;
        }

        $this->server->addPlayer($this);

        // TODO: If not first set.
        if (false) {
            $response = new PangYaBuffer();
            $response->insertArrayBytes([0x0f, 0x00, 0x00]);
            $response->insertPString('test1234');
            $this->send($response);

            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xd9, 0xff, 0xff, 0xff, 0xff]);
            $this->send($response);
        } else {
            $this->sendLoggedOnData();
        }

        return true;
    }

    /**
     * Send player's logged on data.
     *
     * @throws BufferException
     */
    protected function sendLoggedOnData(): void
    {
        $buffer = new PangYaBuffer();
        $buffer->insertArrayBytes([0x10, 0x00]);
        $buffer->insertPString($this->authKeyLogin);
        $this->send($buffer);

        $buffer = new PangYaBuffer();
        $buffer->insertArrayBytes([0x01, 0x00, 0x00]);
        $buffer->insertPString($this->username);
        $buffer->insertInt($this->id);
        $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00]); // ??
        $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00]); // Level
        $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00]); // ??
        $buffer->insertPString($this->nickname);

        $this->send($buffer);

        // Game servers.
        $buffer = new PangYaBuffer();
        $buffer->insertArrayBytes([0x02, 0x00]);
        $buffer->insertByte(1); // Number of servers.

        for ($i = 0; $i < 1; $i++) {
            // Send server data.
            $buffer->insertString('Yui', 10);
            $buffer->insertArrayBytes(array_merge([
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
            ], [
                0x68,
                0x29,
                0x22,
                0x13,
                0x00,
                0x00,
                0x00,
                0x00,
                0x00,
                0x00,
                0x00,
                0x00,
            ]));

            $buffer->insertInt(1); // Server ID.
            $buffer->insertArrayBytes(array_merge([
                0xb0,
                0x03,
                0x00,
                0x00 // Max players.
            ], [
                0x38,
                0x01,
                0x00,
                0x00 // Players online.
            ]));

            // Server address.
            $buffer->insertString($_ENV['GAME_SERVER_HOST'], 16);
            $buffer->insertArrayBytes([0x60, 0x29]);
            // Server port.
            $buffer->insertShort($_ENV['GAME_SERVER_PORT']);

            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x08, 0x00, 0x00]);
            $buffer->insertInt(0); // Angelic number.
            $buffer->insertShort(1); // Img event.
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
            $buffer->insertShort(1); // Img number.
        }

        $this->send($buffer);


        // Messenger servers.
        $buffer = new PangYaBuffer();
        $buffer->insertArrayBytes([0x09, 0x00]);
        $buffer->insertByte(1); // Number of servers.

        for ($i = 0; $i < 1; $i++) {
            $buffer->insertString('Garupan', 20);
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
            $buffer->insertInt(321388144); // Version?
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
            $buffer->insertInt(1); // Server ID.
            $buffer->insertInt(3000); // Max users.
            $buffer->insertInt(10); // Current users.

            // Server address.
            $buffer->insertString($_ENV['MESSENGER_SERVER_HOST'], 16);
            $buffer->insertArrayBytes([0x68, 0xfe]);
            // Server port.
            $buffer->insertShort($_ENV['MESSENGER_SERVER_PORT']);

            $buffer->insertArrayBytes([0x00, 0x00, 0x00]);
            $buffer->insertInt(10); // App rate?.
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
        }

        $this->send($buffer);

        // Macros.
        $buffer = new PangYaBuffer();
        $buffer->insertArrayBytes([0x06, 0x00]);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $buffer->insertString('PangYa!', 64);
        $this->send($buffer);
    }

    /**
     * Create a new character for the player.
     *
     * @param Buffer $buffer
     * @throws BufferException
     */
    public function createCharacter(Buffer $buffer): void
    {
        // TODO
        $characterType = $buffer->getUnsignedInt();
        $hairColor = $buffer->getUnsignedShort();

        $response = new StringBuffer();
        $response->insertArrayBytes([0x11, 0x00, 0x00]);

        $this->send($response);

        $this->sendLoggedOnData();
    }

    /**
     * Set the authentication key used for the game.
     *
     * @throws BufferException
     */
    public function sendGameAuthKey(): void
    {
        $response = new PangYaBuffer();
        $response->insertArrayBytes([0x03, 0x00, 0x00, 0x00, 0x00, 0x00]);
        $response->insertPString($this->authKeyGame);

        $this->send($response);
    }
}
