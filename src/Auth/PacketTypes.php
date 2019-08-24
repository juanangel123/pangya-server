<?php

namespace PangYa\Auth;

/**
 * Class PacketTypes
 *
 * @package PangYa\Auth
 */
class PacketTypes
{
    public const HANDLE_PLAYER_LOGIN = 0x01;
    public const SEND_GAME_AUTH_KEY = 0x03;
    public const HANDLE_DUPLICATE_LOGIN = 0x04;
    public const CREATE_CHARACTER = 0x06;
    public const NICKNAME_CHECK = 0x07;
    public const REQUEST_CHARACTER_CREATE = 0x08;
}
