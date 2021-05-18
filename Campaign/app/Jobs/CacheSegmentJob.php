<?php

namespace App\Jobs;

use App\CampaignSegment;
use App\Contracts\SegmentAggregator;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Log;

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
        $cacheKey = $segmentAggregator::cacheKey($this->campaignSegment);

        if (!$this->force && Redis::connection()->scard($cacheKey)) {
            return;
        }

        if (!$segmentAggregator->cacheEnabled($this->campaignSegment)) {
            return;
        }

        try {
            $users = $segmentAggregator->users($this->campaignSegment);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [
                'provider' => $this->campaignSegment->provider,
                'code' => $this->campaignSegment->code,
            ]);
            throw $e;
        }

        Redis::connection()->del([$cacheKey]);
        if ($users->isNotEmpty()) {
            Redis::connection()->sadd($cacheKey, $users->toArray());
            Redis::connection()->expire($cacheKey, 60*60*24);
        }
    }
}
