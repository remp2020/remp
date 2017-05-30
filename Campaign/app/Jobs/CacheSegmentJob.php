<?php

namespace App\Jobs;

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

    private $segmentId;

    private $force;

    /**
     * Create a new job instance.
     *
     * @param string $segmentId
     * @param bool $force
     */
    public function __construct(string $segmentId, $force = false)
    {
        $this->segmentId = $segmentId;
        $this->force = $force;
    }

    /**
     * Execute the job.
     *
     * @param SegmentContract $segmentContract
     */
    public function handle(SegmentContract $segmentContract)
    {
        $segmentId = $this->segmentId;
        if (!$this->force && Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->get($segmentId)) {
            return;
        }


        $users = $segmentContract->users($segmentId);
        $userIds = $users->map(function($item) {
            return $item->id;
        })->toArray();

        $bloomFilter = new Bloom();
        $bloomFilter->set($userIds);

        Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->put($segmentId, serialize($bloomFilter), 65);
    }
}
