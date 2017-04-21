<?php

namespace App\Models\Dimension;

use Illuminate\Support\Collection;

class Map
{
    /** @var Dimensions[] */
    protected $dimensions = [];

    public function __construct(array $dimensionsConfig)
    {
        foreach ($dimensionsConfig as $key => $dc) {
            $this->dimensions[$key] = new Dimensions(
                $dc['name'],
                $dc['width'],
                $dc['height']
            );
        }
    }

    public function dimensions(): Collection
    {
        return collect($this->dimensions);
    }
}