<?php

namespace Remp\CampaignModule\Contracts;

use Remp\CampaignModule\CampaignSegment;
use Illuminate\Support\Collection;
use Predis\ClientInterface;
use Illuminate\Support\Facades\Redis;

class SegmentAggregator implements SegmentContract
{
    const TAG = 'segments';

    private const SEGMENT_AGGREGATOR_CONFIG_REDIS_KEY = 'segment_aggregator_config';

    /** @var ?SegmentContract[] created on first use, see contracts() */
    private ?array $contracts = null;

    private $errors = [];

    /**
     * @param array $config Config of each segment contract keyed by its factory class (see fromFactories()).
     */
    public function __construct(
        private array $config,
        private ClientInterface|\Redis $redis,
    ) {
        foreach (array_keys($config) as $factoryClass) {
            if (!is_subclass_of($factoryClass, SegmentContractFactory::class)) {
                throw new SegmentException("Unable to create segment contract, [{$factoryClass}] doesn't implement SegmentContractFactory");
            }
        }
    }

    /**
     * @param iterable<SegmentContractFactory> $factories
     */
    public static function fromFactories(iterable $factories, ClientInterface|\Redis $redis): self
    {
        $config = [];
        foreach ($factories as $factory) {
            $config[$factory::class] = $factory->config();
        }
        return new self($config, $redis);
    }

    /**
     * Contracts are created on first use, so showtime doesn't create them for campaigns without segment rules.
     *
     * @return SegmentContract[]
     */
    private function contracts(): array
    {
        if ($this->contracts === null) {
            $this->contracts = [];
            foreach ($this->config as $factoryClass => $factoryConfig) {
                $contract = (new $factoryClass())->create($factoryConfig, $this->redis);
                $this->contracts[$contract->provider()] = $contract;
            }
        }
        return $this->contracts;
    }

    private function contract(CampaignSegment $campaignSegment): ?SegmentContract
    {
        return $this->contracts()[$campaignSegment->provider] ?? null;
    }

    public function provider(): string
    {
        throw new SegmentException("Aggregator cannot return provider value");
    }

    public function list(): Collection
    {
        $collection = collect([]);
        foreach ($this->contracts() as $contract) {
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
        $contract = $this->contract($campaignSegment);
        if (!$contract) {
            return false;
        }

        return $contract->checkUser($campaignSegment, $userId);
    }

    public function checkBrowser(CampaignSegment $campaignSegment, string $browserId): bool
    {
        $contract = $this->contract($campaignSegment);
        if (!$contract) {
            return false;
        }

        return $contract->checkBrowser($campaignSegment, $browserId);
    }

    /**
     * Checks user's membership in cached segments, all segments of each provider at once. Only segments with cache
     * enabled and populated, whose provider implements SegmentCacheBatchContract, are checked. Other segments are left
     * out of the result; check them one by one with checkUser().
     *
     * @param CampaignSegment[] $campaignSegments
     * @return bool[] membership in each checked segment, keys of $campaignSegments are preserved
     */
    public function batchCheckUserInCache(array $campaignSegments, string $userId): array
    {
        $batchContracts = [];
        $segmentsByProvider = [];
        foreach ($campaignSegments as $key => $campaignSegment) {
            $contract = $this->contract($campaignSegment);
            if ($contract instanceof SegmentCacheBatchContract && $contract->cacheEnabled($campaignSegment)) {
                $batchContracts[$campaignSegment->provider] = $contract;
                $segmentsByProvider[$campaignSegment->provider][$key] = $campaignSegment;
            }
        }

        $results = [];
        foreach ($segmentsByProvider as $provider => $providerSegments) {
            $results += $batchContracts[$provider]->batchCheckUserInCache($providerSegments, $userId);
        }

        return $results;
    }

    public function users(CampaignSegment $campaignSegment): Collection
    {
        return $this->contract($campaignSegment)->users($campaignSegment);
    }

    public function cacheEnabled(CampaignSegment $campaignSegment): bool
    {
        $contract = $this->contract($campaignSegment);
        if (!$contract) {
            return false;
        }

        return $contract->cacheEnabled($campaignSegment);
    }

    /**
     * @throws SegmentCacheException Exception is thrown if cache is disabled for segment's provider.
     */
    public function addUserToCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        if (!$this->cacheEnabled($campaignSegment)) {
            throw new SegmentCacheException("Unable to add user to segment's cache. Cache is disabled for this segment provider.");
        }

        return $this->contract($campaignSegment)->addUserToCache($campaignSegment, $userId);
    }

    /**
     * @throws SegmentCacheException Exception is thrown if cache is disabled for segment's provider.
     */
    public function removeUserFromCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        if (!$this->cacheEnabled($campaignSegment)) {
            throw new SegmentCacheException("Unable to remove user from segment's cache. Cache is disabled for this segment provider.");
        }

        return $this->contract($campaignSegment)->removeUserFromCache($campaignSegment, $userId);
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

    /**
     * Key of the time when the segment was cached. The cache is populated only if it exists.
     */
    public static function timestampCacheKey(CampaignSegment $campaignSegment): string
    {
        return self::cacheKey($campaignSegment) . '|timestamp';
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
     * Registered factories are known only within Laravel (tagged in service providers), so their config is cached
     * to Redis to create the aggregator in showtime.php without Laravel.
     */
    public function cacheConfig(): void
    {
        Redis::set(self::SEGMENT_AGGREGATOR_CONFIG_REDIS_KEY, json_encode($this->config));
    }

    public static function fromCachedConfig(ClientInterface|\Redis $redisClient): ?SegmentAggregator
    {
        $config = $redisClient->get(self::SEGMENT_AGGREGATOR_CONFIG_REDIS_KEY);
        if (!$config) {
            return null;
        }

        return new self(json_decode($config, true), $redisClient);
    }
}
