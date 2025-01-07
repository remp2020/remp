<?php

namespace Remp\CampaignModule\Models\Position;

use Illuminate\Support\Collection;

class Map
{
    const POSITIONS_MAP_REDIS_KEY = 'positions_map';

    /** @var Position[] */
    protected array $positions = [];

    public function __construct(array $positionsConfig)
    {
        foreach ($positionsConfig as $key => $dc) {
            $this->positions[$key] = new Position(
                $key,
                $dc['name'],
                $dc['style']
            );
        }
    }

    public function positions(): Collection
    {
        return collect($this->positions);
    }
}
