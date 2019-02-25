<?php

namespace App\Models\Alignment;

use Illuminate\Support\Collection;
use Psy\Util\Json;
use Redis;

class Map
{
    const ALIGNMENTS_MAP_REDIS_KEY = 'dimensions_map';

    /** @var Alignment[] */
    protected $alignments = [];

    public function __construct(array $alignmentsConfig)
    {
        foreach ($alignmentsConfig as $key => $dc) {
            $this->alignments[$key] = new Alignment(
                $key,
                $dc['name'],
                $dc['style']
            );
        }

        Redis::set(self::ALIGNMENTS_MAP_REDIS_KEY, Json::encode($this->alignments));
    }

    public function alignments(): Collection
    {
        return collect($this->alignments);
    }
}
