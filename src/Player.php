<?php

namespace PangYa;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Crypt\Lib;
use PangYa\Crypt\Tables;
use PangYa\Packet\Buffer as PangyaBuffer;
use PangYa\Util\Util;
use React\Socket\ConnectionInterface;

/**
 * This class represents the player.
 *
 * @package PangYa
 */
class Player
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var LoginServer
     */
    protected $loginServer;

    /**
     * @var int Id of the connection.
     */
    protected $id = 0;

    /**
     * The key related to the client.
     * This key works to:
     * - Authenticate the client.
     * - Encrypt / decrypt data.
     *
     * @var int
     */
    protected $key;

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
     * @var string
     */
    protected $auth1;

    /**
     * @var string
     */
    protected $auth2;

    /**
     * ClientPlayer constructor.
     *
     * @param  ConnectionInterface  $connection
     * @param  LoginServer  $loginServer
     */
    public function __construct(ConnectionInterface $connection, LoginServer $loginServer)
    {
        $this->connection = $connection;
        $this->loginServer = $loginServer;
    }

    /**
     * Return the client id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Return the key.
     *
     * @return int
     */
    public function getKey(): int
    {
        return $this->key;
    }

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
     * Connect to the PangYa Server using the connection.
     *
     * @throws Exception
     */
    public function connect(): void
    {
        $this->key = random_int(0, 15); // Random hex.

        // TODO: set a random connection id.
        // Original: pool between 0 to 2999 taken from an array of connections (3000).
        // $this->id = random_int(0, 2999);
        $this->id = 0;

        $this->sendKey();
    }

    /**
     * Disconnect the client.
     */
    public function disconnect(): void
    {
        $this->connection->close();
    }

    /**
     * Send buffer data through the connection.
     *
     * @param  Buffer  $buffer
     * @param  bool  $encrypt
     * @throws BufferException
     */
    protected function send(Buffer $buffer, bool $encrypt = true): void
    {
        if (!$buffer->size()) {
            return;
        }

        if ($encrypt) {
            $buffer = $this->loginServer->getCrypt()->encrypt($buffer, $this->key, 0);
        }

        $this->connection->write($buffer->toString());
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
     * Execute the security check for the data provided.
     *
     * @param  Buffer  $buffer
     * @return bool
     * @throws BufferException
     */
    public function securityCheck(Buffer $buffer): bool
    {
        $rand = $buffer->getUnsignedByte();

        $x = Tables::SECURITY_CHECK_TABLE[($this->key << 8) + $rand];
        $y = Tables::SECURITY_CHECK_TABLE[($this->key << 8) + $rand + 4096];

        if ($y === ($x ^ $buffer->skip(3)->getUnsignedByte())) {
            $buffer->skip(-Lib::MIN_PACKET_SIZE);

            return true;
        }

        $buffer->skip(-Lib::MIN_PACKET_SIZE);

        return false;
    }

    /**
     * Handle player login.
     *
     * @param  PangyaBuffer  $buffer
     * @return bool
     * @throws Exception
     */
    public function handlePlayerLogin(PangyaBuffer $buffer): bool
    {
        if ($this->loginServer->isUnderMaintenance()) {
            $response = new StringBuffer();
            $response->insertArrayBytes([0x01, 0x00, 0xe3, 0x48, 0xd2]);
            $response->insertString(Messages::MAINTENANCE);
            $response->insertByte(0x00);

            $this->send($response);
        }

        if ((!$user = $buffer->readString()) || (!$password = $buffer->readString())) {
            return false;
        }

        dump('user: '.$user);
        dump('password: '.$password);

        // Set auth.
        $this->auth1 = Util::randomAuth(7);
        $this->auth2 = Util::randomAuth(7);

        if ($this->loginServer->getPlayerById($this->id)) {
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

        $this->loginServer->addPlayer($this);

        // TODO: If not first set.
        if (true) {
            $response = new PangyaBuffer();
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
        $buffer = new PangyaBuffer();
        $buffer->insertArrayBytes([0x10, 0x00]);
        $buffer->insertPString($this->auth1);
        $this->send($buffer);

        $buffer = new PangyaBuffer();
        $buffer->insertArrayBytes([0x01, 0x00, 0x00]);
        $buffer->insertPString($this->username);
        $buffer->insertInt($this->id);
        $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00]); // ??
        $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00]); // Level
        $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00]); // ??
        $buffer->insertPString($this->nickname);
        $this->send($buffer);

        // Game servers.
        $buffer = new PangyaBuffer();
        $buffer->insertArrayBytes([0x02, 0x00]);
        $buffer->insertByte(1); // Number of servers.

        for ($i = 0; $i < 1; $i++) {
            // Send server data.
            $buffer->insertString('Test server 1', 10);
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
            $buffer->insertString('127.0.0.1', 16);
            $buffer->insertArrayBytes([0x60, 0x29]);
            // Server port.
            $buffer->insertShort(20020);

            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x08, 0x00, 0x00]);
            $buffer->insertInt(1); // Angelic number.
            $buffer->insertShort(1); // Img event.
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
            $buffer->insertShort(1); // Img number.
        }

        $this->send($buffer);

        // Messenger servers.
        $buffer = new PangyaBuffer();
        $buffer->insertArrayBytes([0x09, 0x00]);
        $buffer->insertByte(1); // Number of servers.

        for ($i = 0; $i < 1; $i++) {
            $buffer->insertString('Test messenger server 1', 20);
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
            $buffer->insertInt(321388144); // Version?
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
            $buffer->insertInt(1); // Server ID.
            $buffer->insertInt(3000); // Max users.
            $buffer->insertInt(10); // Current users.

            // Server address.
            $buffer->insertString('127.0.0.1', 16);
            $buffer->insertArrayBytes([0x68, 0xfe]);
            // Server port.
            $buffer->insertShort(20020);

            $buffer->insertArrayBytes([0x00, 0x00, 0x00]);
            $buffer->insertInt(10); // App rate.
            $buffer->insertArrayBytes([0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00]);
        }

        $this->send($buffer);

        // Macros.
        $buffer = new PangyaBuffer();
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
}
