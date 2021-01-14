<?php

namespace App\Contracts;

use App\CampaignSegment;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializableClosure;
use Predis\Client;
use Redis;

class SegmentAggregator implements SegmentContract
{
    const TAG = 'segments';

    private const SEGMENT_AGGREGATOR_REDIS_KEY = 'segment_aggregator';

    /** @var SegmentContract[] */
    private $contracts = [];

    private $errors = [];

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
            try {
                $list = $contract->list();
                $collection = $collection->merge($list);
            } catch (\Exception $e) {
                $this->errors[] = sprintf("%s: %s", $contract->provider(), $e->getMessage());
            }
        }
        return $collection;
    }

    public function checkUser(CampaignSegment $campaignSegment, string $userId): bool
    {
        return $this->contracts[$campaignSegment->provider]
            ->checkUser($campaignSegment, $userId);
    }

    public function checkBrowser(CampaignSegment $campaignSegment, string $browserId): bool
    {
        return $this->contracts[$campaignSegment->provider]
            ->checkBrowser($campaignSegment, $browserId);
    }

    public function users(CampaignSegment $campaignSegment): Collection
    {
        return $this->contracts[$campaignSegment->provider]
            ->users($campaignSegment);
    }

    public function cacheEnabled(CampaignSegment $campaignSegment): bool
    {
        return $this->contracts[$campaignSegment->provider]
            ->cacheEnabled($campaignSegment);
    }

    public function setProviderData($cache): void
    {
        foreach ($this->contracts as $provider => $contract) {
            if ($cache && isset($cache->$provider)) {
                $contract->setProviderData($cache->$provider);
            }
        }
    }

    public function getProviderData()
    {
        $cache = new \stdClass;
        foreach ($this->contracts as $provider => $contract) {
            if ($cc = $contract->getProviderData()) {
                $cache->$provider = $cc;
            }
        }
        return $cache;
    }

    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * SegmentAggregator contains Guzzle clients which have properties defined as closures.
     * It's not possible to serialize closures in plain PHP, but Laravel provides a workaround.
     * This will store a function returning segmentAggregator into the redis which can be later
     * used in plain PHP to bypass Laravel initialization just to get the aggregator.
     */
    public function serializeToRedis()
    {
        $serializableClosure = new SerializableClosure(function () {
            return $this;
        });
        Redis::set(self::SEGMENT_AGGREGATOR_REDIS_KEY, serialize($serializableClosure));

        $dimensionMap = app(\App\Models\Dimension\Map::class);
        $positionsMap = app(\App\Models\Position\Map::class);
        $alignmentsMap = app(\App\Models\Alignment\Map::class);

        Redis::set(\App\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY, $dimensionMap->dimensions()->toJson());
        Redis::set(\App\Models\Position\Map::POSITIONS_MAP_REDIS_KEY, $positionsMap->positions()->toJson());
        Redis::set(\App\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY, $alignmentsMap->alignments()->toJson());
    }

    public static function unserializeFromRedis(Client $redisClient): ?SegmentAggregator
    {
        $serializedClosure = $redisClient->get(self::SEGMENT_AGGREGATOR_REDIS_KEY);
        return $serializedClosure ? unserialize($serializedClosure)() : null;
    }
}
