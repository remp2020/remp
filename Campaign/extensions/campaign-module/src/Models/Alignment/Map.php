<?php

namespace Remp\CampaignModule\Models\Alignment;

use Illuminate\Support\Collection;

class Map
{
    const ALIGNMENTS_MAP_REDIS_KEY = 'alignments_map';

    /** @var Alignment[] */
    protected array $alignments = [];

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
