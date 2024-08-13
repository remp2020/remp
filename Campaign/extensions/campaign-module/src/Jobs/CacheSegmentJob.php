<?php

namespace Remp\CampaignModule\Jobs;

use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\SegmentAggregator;
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
        $cacheTimestampKey = "{$cacheKey}|timestamp";

        if (!$this->force && Redis::connection()->get($cacheTimestampKey)) {
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

        $redis = Redis::connection()->client();

        $redis->setex($cacheTimestampKey, 60*60*24, date('U'));
        $redis->del([$cacheKey]);
        if ($users->isNotEmpty()) {
            $userChunks = array_chunk($users->toArray(), config('redis.redis_parameter_limit'));
            foreach ($userChunks as $users) {
                if ($redis instanceof \Redis) {
                    $redis->sAdd($cacheKey, ...$users);
                } else {
                    $redis->sadd($cacheKey, $users);
                }
            }
            $redis->expire($cacheKey, 60*60*24);
        }
    }
}
