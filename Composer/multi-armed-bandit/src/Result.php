<?php

namespace Remp\MultiArmedBandit;

class Result
{
    protected $levers;

    protected $probabilities;

    public function __construct(Lever $lever, float $probability)
    {
        $this->levers[$lever->getId()] = $lever;
        $this->probabilities[$lever->getId()] = $probability;
    }

    public function getWinningLever(): Lever
    {
        $levers = $this->levers;
        arsort($levers);
        return $this->levers[key($levers)];
    }

    public function getWinningLeverProbability(): float
    {
        return $this->probabilities[$this->getWinningLever()->getId()] ?? 0;
    }
}
