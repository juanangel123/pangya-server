<?php

namespace PangYa;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Crypt\Lib;
use PangYa\Crypt\Tables;
use PangYa\Packet\Buffer as PangyaBuffer;
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
     * @var bool Flag to check if the client has been verified to send packets.
     */
    protected $verified;

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
     * @throws BufferException
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

        $connector = new \React\Socket\Connector($this->loginServer->getLoop());
        $connector->connect('127.0.0.1:10110')->then(function (\React\Socket\ConnectionInterface $connection) {
            $connection->on('data', function (string $data) {
                echo "Client data: ".$data."\n";
            });
            $buffer = new StringBuffer();
            $buffer->insertArrayBytes([0x03, 0x00]);
            $buffer->insertInt($this->getId());

            $connection->write($buffer->toString());
        });

        /*
        if Self.FFirstSet = 0 then
    begin
      Reply.Clear;
      Reply.WriteStr(#$0F#$00#$00);
          Reply.WritePStr(Self.GetPlayerLogin);
      Self.Send(Reply);

      Reply.Clear;
      Reply.WriteStr(#$01#$00#$D9#$FF#$FF#$FF#$FF);
          Self.Send(Reply);
      Exit;
    end;
*/

        // TODO: If not first set.
        if (true) {
            $buffer2 = new StringBuffer();
            $buffer2->insertArrayBytes([0x0f, 0x00, 0x00]);
            $buffer2->insertInt(strlen('test1234'));
            $buffer2->insertString('test1234');
            $this->send($buffer2);

            $buffer2 = new StringBuffer();
            $buffer2->insertArrayBytes([0x01, 0x00, 0xd9, 0xff, 0xff, 0xff, 0xff]);
            $this->send($buffer2);
        }

        return true;
    }
}
