<?php

namespace PangYa\Game;

use PangYa\Game\Player\Statistics;

/**
 * Class Player
 *
 * @package PangYa\Game
 */
class Player
{
    /**
     * @var Statistics
     */
    protected $statistics;

    /**
     * Player constructor.
     */
    public function __construct()
    {
        $this->statistics = new Statistics();
    }

    /**
     * @return Statistics
     */
    public function getStatistics(): Statistics
    {
        return $this->statistics;
    }
}
