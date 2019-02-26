<?php

namespace App\Models\Dimension;

use Illuminate\Support\Collection;
use Psy\Util\Json;
use Redis;

class Map
{
    const DIMENSIONS_MAP_REDIS_KEY = 'dimensions_map';

    /** @var Dimensions[] */
    protected $dimensions = [];

    public function __construct(array $dimensionsConfig)
    {
        foreach ($dimensionsConfig as $key => $dc) {
            $this->dimensions[$key] = new Dimensions(
                $key,
                $dc['name'],
                $dc['width'],
                $dc['height']
            );
        }

        Redis::set(self::DIMENSIONS_MAP_REDIS_KEY, Json::encode($this->dimensions));
    }

    public function dimensions(): Collection
    {
        return collect($this->dimensions);
    }
}
