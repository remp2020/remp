<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

class SegmentAggregator implements SegmentContract
{
    /** @var SegmentContract[] */
    private $contracts;

    public function __construct($segmentContracts)
    {
        $this->contracts = $segmentContracts;
    }

    public function list(): Collection
    {
        $collection = collect([]);
        foreach ($this->contracts as $contract) {
            $list = $contract->list();
            $collection = $collection->merge($list);
        }
        return $collection;
    }

    public function check($segmentId, $userId): bool
    {
        return true;
    }

    public function users($segmentId): Collection
    {
        return collect([]);
    }
}