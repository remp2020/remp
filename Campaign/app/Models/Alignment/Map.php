<?php

namespace App\Models\Alignment;

use Illuminate\Support\Collection;

class Map
{
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
    }

    public function alignments(): Collection
    {
        return collect($this->alignments);
    }
}