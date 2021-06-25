<?php

namespace Remp\MultiArmedBandit;

/**
 * Results for individual arms.
 *
 * @package Remp\MultiArmedBandit
 */
class Lever
{
    protected $id;

    protected $rewards;

    protected $attempts;

    public function __construct(string $id, int $rewards, int $attempts)
    {
        $this->id = $id;
        $this->rewards = $rewards;
        $this->attempts = $attempts;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRewards(): int
    {
        return $this->rewards;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }
}
