<?php

namespace Remp\MultiArmedBandit;

use gburtini\Distributions\Beta;

class Machine
{
    protected $draws;

    /** @var Lever[] */
    protected $levers;

    public function __construct(int $draws = 1000)
    {
        $this->draws = $draws;
    }

    public function addLever(Lever $lever)
    {
        $this->levers[$lever->getId()] = $lever;
    }

    public function run(): Result
    {
        $results = [];
        foreach (range(1, $this->draws) as $_) {
            $simulatedData = [];

            foreach ($this->levers as $lever => $arm) {
                // get random variates
                $beta = new Beta($arm->getRewards(), $arm->getAttempts() - $arm->getRewards());
                $simulatedData[$lever] = $beta->rand();
            }

            // select winning lever
            arsort($simulatedData);
            $results[] = key($simulatedData);
        }

        // calculate probability of win for each lever based on number of draws
        $distribution = array_count_values($results);
        arsort($distribution);

        // return winning lever data
        $key = key($distribution);
        $winningLever = $this->levers[$key];
        return new Result($winningLever, $distribution[$key] / $this->draws);
    }
}
