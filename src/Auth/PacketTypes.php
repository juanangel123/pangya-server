<?php

namespace PangYa\Auth;

/**
 * Class PacketTypes
 *
 * @package PangYa\Auth
 */
class PacketTypes
{
    public const HANDLE_PLAYER_LOGIN = 1;
    public const SEND_GAME_AUTH_KEY = 3;
    public const HANDLE_DUPLICATE_LOGIN = 4;
    public const CREATE_CHARACTER = 6;
    public const NICKNAME_CHECK = 7;
    public const REQUEST_CHARACTER_CREATE = 8;
}
