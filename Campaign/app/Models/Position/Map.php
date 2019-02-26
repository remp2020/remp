<?php

namespace App\Models\Position;

use Illuminate\Support\Collection;
use Psy\Util\Json;
use Redis;

class Map
{
    const POSITIONS_MAP_REDIS_KEY = 'positions_map';

    /** @var Position[] */
    protected $positions = [];

    public function __construct(array $positionsConfig)
    {
        foreach ($positionsConfig as $key => $dc) {
            $this->positions[$key] = new Position(
                $key,
                $dc['name'],
                $dc['style']
            );
        }

        Redis::set(self::POSITIONS_MAP_REDIS_KEY, Json::encode($this->positions));
    }

    public function positions(): Collection
    {
        return collect($this->positions);
    }
}
