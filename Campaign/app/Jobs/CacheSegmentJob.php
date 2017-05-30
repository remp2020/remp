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

    /**
     * Create a new job instance.
     *
     * @param string $segmentId
     */
    public function __construct(string $segmentId)
    {
        $this->segmentId = $segmentId;
    }

    /**
     * Execute the job.
     *
     * @param SegmentContract $segmentContract
     */
    public function handle(SegmentContract $segmentContract)
    {
        $segmentId = $this->segmentId;
        $users = $segmentContract->users($this->segmentId);

        $bloomFilter = new Bloom();
        $userIds = $users->map(function($item) {
            return $item->id;
        })->toArray();
        $bloomFilter->set($userIds);

        Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->put($segmentId, serialize($bloomFilter), 60);
    }
}
