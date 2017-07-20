<?php

namespace App\Contracts;

use App\CampaignSegment;
use Illuminate\Support\Collection;

class SegmentAggregator implements SegmentContract
{
    const TAG = 'segments';

    /** @var SegmentContract[] */
    private $contracts;

    public function __construct($segmentContracts)
    {
        /** @var SegmentContract $contract */
        foreach ($segmentContracts as $contract) {
            $this->contracts[$contract->provider()] = $contract;
        }
    }

    public function provider(): string
    {
        throw new SegmentException("Aggregator cannot return provider value");
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

    public function check(CampaignSegment $campaignSegment, $userId): bool
    {
        return $this->contracts[$campaignSegment->provider]
            ->check($campaignSegment, $userId);
    }

    public function users($segmentId): Collection
    {
        return collect([]);
    }
}
