<?php

namespace PangYa\Game\Player;

/**
 * This class stores game statistics.
 *
 * @package PangYa\Game\Player
 */
class Statistics
{
    /**
     * @var int
     */
    public $drive = 0;

    /**
     * @var int
     */
    public $putt = 0;

    /**
     * @var int
     */
    public $playTime = 0;

    /**
     * @var int
     */
    public $shotTime = 0;

    /**
     * @var int
     */
    public $longestDistance = 0;

    /**
     * @var int
     */
    public $pangYa = 0;

    /**
     * @var int
     */
    public $timeOut = 0;

    /**
     * @var int
     */
    public $ob = 0;

    /**
     * @var int
     */
    public $distanceTotal = 0;

    /**
     * @var int
     */
    public $hole = 0;

    /**
     * @var int
     */
    public $teamHole = 0;

    /**
     * @var int
     */
    public $hio = 0;

    /**
     * @var int
     */
    public $bunker = 0;

    /**
     * @var int
     */
    public $fairway = 0;

    /**
     * @var int
     */
    public $albatross = 0;

    /**
     * @var int
     */
    public $holeIn = 0;

    /**
     * @var int
     */
    public $puttIn = 0;

    /**
     * @var int
     */
    public $longestPutt = 0;

    /**
     * @var int
     */
    public $longestChip = 0;

    /**
     * @var int
     */
    public $exp = 0;

    /**
     * @var int
     */
    public $level = 1;

    /**
     * @var int
     */
    public $pang = 0;

    /**
     * @var int
     */
    public $totalScore = 0;

    /**
     * Best scores.
     *
     * @var array
     */
    public $score = [0, 0, 0, 0, 0];

    /**
     * @var int
     */
    public $maxPang0 = 0;

    /**
     * @var int
     */
    public $maxPang1 = 0;

    /**
     * @var int
     */
    public $maxPang2 = 0;

    /**
     * @var int
     */
    public $maxPang3 = 0;

    /**
     * @var int
     */
    public $maxPang4 = 0;

    /**
     * @var int
     */
    public $sumPang;

    /**
     * @var int
     */
    public $gamePlayed = 0;

    /**
     * @var int
     */
    public $disconnected = 0;

    /**
     * @var int
     */
    public $teamWin = 0;

    /**
     * @var int
     */
    public $teamGame = 0;

    /**
     * @var int
     */
    public $ladderPoint = 0;

    /**
     * @var int
     */
    public $ladderWin = 0;

    /**
     * @var int
     */
    public $ladderLose = 0;

    /**
     * @var int
     */
    public $ladderDraw = 0;

    /**
     * @var int
     */
    public $ladderHole = 0;

    /**
     * @var int
     */
    public $comboCount = 0;

    /**
     * @var int
     */
    public $maxCombo = 0;

    /**
     * @var int
     */
    public $noMannerGameCount = 0;

    /**
     * @var int
     */
    public $skinsPang = 0;

    /**
     * @var int
     */
    public $skinsWin = 0;

    /**
     * @var int
     */
    public $skinsLose = 0;

    /**
     * @var int
     */
    public $skinsRunHole = 0;

    /**
     * @var int
     */
    public $skinsStrikePoint = 0;

    /**
     * @var int
     */
    public $skinsAllInCount = 0;

    /**
     * @var int
     */
    public $gameCountSeason = 0;

    /**
     * @var int
     */
    public $unknown;

    /**
     * @var array
     */
    public $unknown1 = [0x00, 0x00, 0x00, 0x00, 0x00];

    /**
     * @var array
     */
    public $unknown2 = [0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00];
}
