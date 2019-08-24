<?php

namespace PangYa\Game;

/**
 * Class PacketTypes
 *
 * @package PangYa\Game
 */
class PacketTypes
{
    public const PLAYER_LOGIN = 0x0002;
    public const PLAYER_CHAT = 0x0003;
    public const PLAYER_SELECT_LOBBY = 0x0004;

    public const PLAYER_CREATE_GAME = 0x0008;
    public const PLAYER_JOIN_GAME = 0x0009;

    public const PLAYER_CHANGE_NICKNAME = 0x0038;
    public const PLAYER_JOIN_MULTI_GAME_LIST = 0x0081;
    public const PLAYER_LEAVE_MULTI_GAME_LIST = 0x0082;
    public const PLAYER_JOIN_MULTI_GAME_GRAND_PRIX = 0x0176;
    public const PLAYER_LEAVE_MULTI_GAME_GRAND_PRIX = 0x0177;
    public const PLAYER_ENTER_GRAND_PRIX = 0x0179;
    public const PLAYER_OPEN_PAPEL = 0x0098;
    public const PLAYER_OPEN_NORMAL_BONGDARI = 0x014b;
    public const PLAYER_OPEN_BIG_BONGDARI = 0x0186;
    public const PLAYER_SAVE_MACRO = 0x0069;
    public const PLAYER_OPEN_MAILBOX = 0x0143;
    public const PLAYER_READ_MAIL = 0x0144;
    public const PLAYER_RELEASE_MAIL_ITEM = 0x0146;
    public const PLAYER_DELETE_MAIL = 0x0147;
    public const PLAYER_GM_COMMAND = 0x008f;

    // Game process.
    public const PLAYER_USE_ITEM = 0x0017;
    public const PLAYER_PRESS_READY = 0x000d;
    public const PLAYER_START_GAME = 0x000e;
    public const PLAYER_LEAVE_GAME = 0x000f;
    public const PLAYER_LOAD_OK = 0x0011;
    public const PLAYER_SHOT_DATA = 0x001b;
    public const PLAYER_ENTER_TO_ROOM = 0x00eb; // May be used for chat room only.
    public const PLAYER_ACTION = 0x0063;
    public const PLAYER_CLOSE_SHOP = 0x0075;
    public const PLAYER_EDIT_SHOP = 0x0076;
    public const PLAYER_MASTER_KICK_PLAYER = 0x0026;
    public const PLAYER_CHANGE_GAME_OPTION = 0x000a;
    public const PLAYER_LEAVE_GRAND_PRIX = 0x017a;
    public const PLAYER_AFTER_UPLOAD_UCC = 0x00b9;
    public const PLAYER_REQUEST_UPLOAD_KEY = 0x00c9;
    public const PLAYER_1ST_SHOT_READY = 0x0034;
    public const PLAYER_LOADING_INFO = 0x0048;
    public const PLAYER_GAME_ROTATE = 0x0013;
    public const PLAYER_CHANGE_CLUB = 0x0016;
    public const PLAYER_GAME_MARK = 0x012e;
    public const PLAYER_ACTION_SHOT = 0x0012;
    public const PLAYER_SHOT_SYNC = 0x001c;
    public const PLAYER_HOLE_INFORMATION = 0x001a;
    public const PLAYER_MY_TURN = 0x0022;
    public const PLAYER_HOLE_COMPLETE = 0x0031;
    public const PLAYER_CHAT_ICON = 0x0018;
    public const PLAYER_SLEEP_ICON = 0x0032;
    public const PLAYER_MATCH_DATA = 0x012f;
    public const PLAYER_MOVE_BAR = 0x0014;
    public const PLAYER_PAUSE_GAME = 0x0030;
    public const PLAYER_QUIT_SINGLE_PLAYER = 0x0130;
    public const PLAYER_CALL_ASSIST_PUTTING = 0x0185;
    public const PLAYER_USE_TIME_BOOSTER = 0x0065;
    public const PLAYER_DROP_BALL = 0x0019;
    public const PLAYER_CHANGE_TEAM = 0x0010;
    public const PLAYER_VERSUS_TEAM_SCORE = 0x0035;
    public const PLAYER_POWER_SHOT = 0x0015;
    public const PLAYER_WIND_CHANGE = 0x0141;
    public const PLAYER_SEND_GAME_RESULT = 0x0006;

    // Item special.
    public const PLAYER_REQUEST_ANIMAL_HAND_EFFECT = 0x015c;

    public const PLAYER_BUY_ITEM_GAME = 0x001d;
    public const PLAYER_ENTER_TO_SHOP = 0x0140;
    public const PLAYER_CHECK_USER_FOR_GIFT = 0x0007;

    public const PLAYER_SAVE_BAR = 0x000b;
    public const PLAYER_CHANGE_EQUIPMENT = 0x000c;
    public const PLAYER_CHANGE_EQUIPMENTS = 0x0020;

    public const PLAYER_WHISPER = 0x002A;
    public const PLAYER_REQUEST_TIME = 0x005c;
    public const PLAYER_GM_DESTROY_ROOM = 0x0060;
    public const PLAYER_GM_KICK_USER = 0x0061;
    public const PLAYER_REQUEST_LOBBY_INFO = 0x0043;
    public const PLAYER_REMOVE_ITEM = 0x0064;
    public const PLAYER_PLAY_AZTEC_BOX = 0x00ec;
    public const PLAYER_OPEN_BOX = 0x00Ef;
    public const PLAYER_CHANGE_SERVER = 0x0119;
    public const PLAYER_ASSIST_CONTROL = 0x0184;
    public const PLAYER_SELECT_LOBBY_WITH_ENTER_CHANNEL = 0x0083;
    public const PLAYER_REQUEST_GAMEINFO = 0x002d;
    public const PLAYER_GM_SEND_NOTICE = 0x0057;
    public const PLAYER_REQUEST_PLAYERINFO = 0x002f;
    public const PLAYER_CHANGE_MASCOT_MESSAGE = 0x0073;
    public const PLAYER_ENTER_ROOM = 0x00b5;
    public const PLAYER_ENTER_ROOM_GETINFO = 0x00b7;

    public const PLAYER_OPENUP_SCRATCHCARD = 0x012a;
    public const PLAYER_PLAY_SCRATCHCARD = 0x0070;

    public const PLAYER_FIRST_SET_LOCKER = 0x00d0;
    public const PLAYER_ENTER_TO_LOCKER = 0x00d3;
    public const PLAYER_OPEN_LOCKER = 0x00cc;
    public const PLAYER_CHANGE_LOCKER_PWD = 0x00d1;
    public const PLAYER_GET_LOCKER_PANG = 0x00d5;
    public const PLAYER_LOCKER_PANG_CONTROL = 0x00d4;
    public const PLAYER_CALL_LOCKER_ITEM_LIST = 0x00cd;
    public const PLAYER_PUT_ITEM_LOCKER = 0x00ce;
    public const PLAYER_TAKE_ITEM_LOCKER = 0x00cf;

    // Club.
    public const PLAYER_UPGRADE_CLUB = 0x0164;
    public const PLAYER_UPGRADE_ACCEPT = 0x0165;
    public const PLAYER_UPGRADE_CALCEL = 0x0166;
    public const PLAYER_UPGRADE_RANK = 0x0167;
    public const PLAYER_TRANSFER_CLUB_POINT = 0x016c;
    public const PLAYER_CLUB_SET_ABBOT = 0x016b;
    public const PLAYER_CLUB_SET_POWER = 0x016d;
    public const PLAYER_CHANGE_INTRO = 0x0106;
    public const PLAYER_CHANGE_NOTICE = 0x0105;
    public const PLAYER_CHANGE_SELF_INTRO = 0x0111;
    public const PLAYER_LEAVE_GUILD = 0x0113;
    public const PLAYER_UPGRADE_CLUB_SLOT = 0x004b;

    // Guild system.
    public const PLAYER_CALL_GUILD_LIST = 0x0108;
    public const PLAYER_SEARCH_GUILD = 0x0109;
    public const PLAYER_GUILD_AVAILABLE = 0x0102;
    public const PLAYER_CREATE_GUILD = 0x0101;
    public const PLAYER_REQUEST_GUILD_DATA = 0x0104;
    public const PLAYER_GUILD_GET_PLAYER = 0x0112;
    public const PLAYER_GUILD_LOG = 0x010a;
    public const PLAYER_JOIN_GUILD = 0x010c;
    public const PLAYER_CANCEL_JOIN_GUILD = 0x010d;
    public const PLAYER_GUILD_ACCEPT = 0x010e;
    public const PLAYER_GUILD_KICK = 0x0114;
    public const PLAYER_GUILD_PROMOTE = 0x0110;
    public const PLAYER_GUILD_DESTROY = 0x0107;
    public const PLAYER_GUILD_CALL_UPLOAD = 0x0115;
    public const PLAYER_GUILD_CALL_AFTER_UPLOAD = 0x0116;

    // Daily login.
    public const PLAYER_REQUEST_CHECK_DAILY_ITEM = 0x016e;
    public const PLAYER_REQUEST_ITEM_DAILY = 0x016f;

    // Achievement.
    public const PLAYER_CALL_ACHIEVEMENT = 0x0157;

    // Tiki report.
    public const PLAYER_OPEN_TIKI_REPORT = 0x00ab;

    // Unknown yet.
    public const PLAYER_REQUEST_UNKNOWN = 0x00c1;

    // Memorial.
    public const PLAYER_MEMORIAL = 0x017f;

    // Card Pack Open.
    public const PLAYER_OPEN_CARD = 0x00ca;
    public const PLAYER_CARD_SPECIAL = 0x00bd;

    public const PLAYER_CALL_CUT_IN = 0x00e5;

    // Magic Box.
    public const PLAYER_DO_MAGIC_BOX = 0x0158;

    // Rent item.
    public const PLAYER_RENEW_RENT = 0x00e6;
    public const PLAYER_DELETE_RENT = 0x00e7;

    // Quest.
    public const PLAYER_LOAD_QUEST = 0x0151;
    public const PLAYER_ACCEPT_QUEST = 0x0152;

    // Card insert.
    public const PLAYER_PUT_CARD = 0x018a;
    public const PLAYER_PUT_BONUS_CARD = 0x018b;
    public const PLAYER_REMOVE_CARD = 0x018c;

    public const PLAYER_MATCH_HISTORY = 0x009c;

    // Top notice.
    public const PLAYER_SEND_TOP_NOTICE = 0x0066;
    public const PLAYER_CHECK_NOTICE_COOKIE = 0x0067;

    public const PLAYER_UPGRADE_STATUS = 0x0188;
    public const PLAYER_DOWNGRADE_STATUS = 0x0189;
}
