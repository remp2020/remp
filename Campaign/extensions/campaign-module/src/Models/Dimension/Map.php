<?php

namespace Remp\CampaignModule\Models\Dimension;

use Illuminate\Support\Collection;

class Map
{
    const DIMENSIONS_MAP_REDIS_KEY = 'dimensions_map';

    /** @var Dimensions[] */
    protected array $dimensions = [];

    public function __construct(array $dimensionsConfig)
    {
        foreach ($dimensionsConfig as $key => $dc) {
            $this->dimensions[$key] = new Dimensions(
                $key,
                $dc['name'],
                $dc['width'],
                $dc['height'],
                $dc['force_manual_tracking'] ?? false
            );
        }
    }

    public function dimensions(): Collection
    {
        return collect($this->dimensions);
    }
}
