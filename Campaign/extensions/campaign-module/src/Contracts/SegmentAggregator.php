<?php

namespace Remp\CampaignModule\Contracts;

use Remp\CampaignModule\CampaignSegment;
use Illuminate\Support\Collection;
use Laravel\SerializableClosure\SerializableClosure;
use Predis\Client;
use Illuminate\Support\Facades\Redis;

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
        if (!isset($this->contracts[$campaignSegment->provider])) {
            return false;
        }

        return $this->contracts[$campaignSegment->provider]
            ->checkUser($campaignSegment, $userId);
    }

    public function checkBrowser(CampaignSegment $campaignSegment, string $browserId): bool
    {
        if (!isset($this->contracts[$campaignSegment->provider])) {
            return false;
        }

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
        if (!isset($this->contracts[$campaignSegment->provider])) {
            return false;
        }

        return $this->contracts[$campaignSegment->provider]
            ->cacheEnabled($campaignSegment);
    }

    /**
     * @throws SegmentCacheException Exception is thrown if cache is disabled for segment's provider.
     */
    public function addUserToCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        if (!$this->cacheEnabled($campaignSegment)) {
            throw new SegmentCacheException("Unable to add user to segment's cache. Cache is disabled for this segment provider.");
        }

        return $this->contracts[$campaignSegment->provider]
            ->addUserToCache($campaignSegment, $userId);
    }

    /**
     * @throws SegmentCacheException Exception is thrown if cache is disabled for segment's provider.
     */
    public function removeUserFromCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        if (!$this->cacheEnabled($campaignSegment)) {
            throw new SegmentCacheException("Unable to remove user from segment's cache. Cache is disabled for this segment provider.");
        }

        return $this->contracts[$campaignSegment->provider]
            ->removeUserFromCache($campaignSegment, $userId);
    }

    /**
     * Key returns unique key under which the data for given campaignSegment are cached.
     *
     * @return string
     */
    public static function cacheKey(CampaignSegment $campaignSegment): string
    {
        return "{$campaignSegment->provider}|{$campaignSegment->code}";
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

    public function refreshRedisClient(Client|\Redis $redisClient): void
    {
        foreach ($this->contracts as $contract) {
            if ($contract instanceof RedisAwareInterface) {
                $contract->setRedisClient($redisClient);
            }
        }
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

        $dimensionMap = app(\Remp\CampaignModule\Models\Dimension\Map::class);
        $positionsMap = app(\Remp\CampaignModule\Models\Position\Map::class);
        $alignmentsMap = app(\Remp\CampaignModule\Models\Alignment\Map::class);
        $colorSchemesMap = app(\Remp\CampaignModule\Models\ColorScheme\Map::class);

        Redis::set(\Remp\CampaignModule\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY, $dimensionMap->dimensions()->toJson());
        Redis::set(\Remp\CampaignModule\Models\Position\Map::POSITIONS_MAP_REDIS_KEY, $positionsMap->positions()->toJson());
        Redis::set(\Remp\CampaignModule\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY, $alignmentsMap->alignments()->toJson());
        Redis::set(\Remp\CampaignModule\Models\ColorScheme\Map::COLOR_SCHEMES_MAP_REDIS_KEY, $colorSchemesMap->colorSchemes()->toJson());
    }

    public static function unserializeFromRedis(Client|\Redis $redisClient): ?SegmentAggregator
    {
        $serializedClosure = $redisClient->get(self::SEGMENT_AGGREGATOR_REDIS_KEY);

        /* @var ?SegmentAggregator $segmentAggregator */
        $segmentAggregator = $serializedClosure ? unserialize($serializedClosure)() : null;

        // set the redis to avoid duplicated connection
        $segmentAggregator?->refreshRedisClient($redisClient);

        return $segmentAggregator;
    }
}
