<?php

namespace App\Models\Position;

use Illuminate\Support\Collection;

class Map
{
    /** @var Position[] */
    protected $positions = [];

    public function __construct(array $positionsConfig)
    {
        foreach ($positionsConfig as $key => $dc) {
            $this->positions[$key] = new Position(
                $key,
                $dc['name']
            );
        }
    }

    public function positions(): Collection
    {
        return collect($this->positions);
    }
}
