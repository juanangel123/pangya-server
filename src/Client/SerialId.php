<?php

namespace PangYa\Client;

/**
 * Class SerialId
 *
 * @package PangYa\Client
 */
class SerialId
{
    /**
     * @var int Max number of ids.
     */
    protected const MAX = 2000;

    /**
     * @var array
     */
    protected $uniqueIds = [];

    /**
     * Return a unique id.
     *
     * @return null|int
     */
    public function getId(): ?int
    {
        for ($i = 0; $i < self::MAX; $i++) {
            if (!isset($this->uniqueIds[$i])) {
                $this->uniqueIds[$i] = true;

                return $i;
            }
        }

        return null;
    }

    /**
     * Remove an id from the unique ids.
     *
     * @return bool
     */
    public function removeId(): bool
    {
        for ($i = 0; $i < self::MAX; $i++) {
            if (isset($this->uniqueIds[$i])) {
                unset($this->uniqueIds[$i]);

                return true;
            }
        }

        return false;
    }
}
