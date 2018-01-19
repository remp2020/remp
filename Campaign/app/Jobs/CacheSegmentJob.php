<?php

namespace App\Jobs;

use App\CampaignSegment;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentContract;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Razorpay\BloomFilter\Bloom;

class CacheSegmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $campaignSegment;

    private $force;

    /**
     * Create a new job instance.
     *
     * @param CampaignSegment $campaignSegment
     * @param bool $force
     */
    public function __construct(CampaignSegment $campaignSegment, $force = false)
    {
        $this->campaignSegment = $campaignSegment;
        $this->force = $force;
    }

    /**
     * Execute the job.
     *
     * @param SegmentAggregator $segmentAggregator
     */
    public function handle(SegmentAggregator $segmentAggregator)
    {
        if (!$this->force && Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->get($this->key())) {
            return;
        }

        if (!$segmentAggregator->cacheEnabled($this->campaignSegment)) {
            return;
        }

        $users = $segmentAggregator->users($this->campaignSegment);
        $userIds = $users->map(function ($item) {
            return $item->id;
        })->toArray();

        $bloomFilter = new Bloom([
            'entries_max' => count($userIds),
            'error_chance' => 0.001
        ]);
        $bloomFilter->set($userIds);

        Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->put($this->key(), $bloomFilter, 60*24);
    }

    /**
     * Key returns unique key under which the data for given campaignSegment are cached.
     *
     * @return string
     */
    public function key(): string
    {
        return "{$this->campaignSegment->provider}|{$this->campaignSegment->code}";
    }
}
