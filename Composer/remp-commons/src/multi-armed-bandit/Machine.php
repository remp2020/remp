<?php

namespace Remp\MultiArmedBandit;

use gburtini\Distributions\Beta;

class Machine
{
    protected $draws;

    /** @var Lever[] */
    protected $levers = [];

    public function __construct(int $draws = 1000)
    {
        $this->draws = $draws;
    }

    public function addLever(Lever $lever)
    {
        $this->levers[$lever->getId()] = $lever;
    }

    /**
     * @return float[]
     */
    public function run(): array
    {
        $results = [];
        if (empty($this->levers)) {
            return [];
        }

        foreach (range(1, $this->draws) as $_) {
            $simulatedData = [];

            foreach ($this->levers as $lever => $arm) {
                if ($arm->getAttempts() === $arm->getRewards()) {
                    $simulatedData[$lever] = 1;
                    continue;
                }

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
        $probabilities = [];
        foreach ($this->levers as $leverId => $lever) {
            $probabilities[$leverId] = ($distribution[$leverId] ?? 0) / $this->draws;
        }
        return $probabilities;
    }
}
