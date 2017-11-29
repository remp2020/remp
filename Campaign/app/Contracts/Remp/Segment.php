<?php

namespace App\Contracts\Remp;

use App\CampaignSegment;
use App\Contracts\SegmentContract;
use App\Contracts\SegmentException;
use App\Jobs\CacheSegmentJob;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;
use Psy\Util\Json;
use Razorpay\BloomFilter\Bloom;

class Segment implements SegmentContract
{
    const PROVIDER_ALIAS = 'remp_segment';

    const ENDPOINT_LIST = 'segments';

    const ENDPOINT_CHECK = 'segments/%s/check/%s';

    const ENDPOINT_USERS = 'segments/%s/users';

    private $client;

    private $cacheEnabled;

    private $cache;

    private $eventRules;

    private $overridableFields;

    public function __construct(Client $client, $cacheEnabled)
    {
        $this->client = $client;
        $this->cacheEnabled = $cacheEnabled;
        $this->cache = new \stdClass;
        $this->eventRules = new \stdClass;
    }

    public function provider(): string
    {
        return static::PROVIDER_ALIAS;
    }

    /**
     * @return Collection
     * @throws SegmentException
     */
    public function list(): Collection
    {
        try {
            $response = $this->client->get(self::ENDPOINT_LIST);
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:List endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        $campaignSegments = [];
        foreach ($list as $item) {
            $cs = new CampaignSegment();
            $cs->name = $item->name;
            $cs->provider = self::PROVIDER_ALIAS;
            $cs->code = $item->code;
            $cs->group = $item->group;
            $campaignSegments[] = $cs;
        }
        $collection = collect($campaignSegments);
        return $collection;
    }

    /**
     * @param CampaignSegment $campaignSegment
     * @param $userId
     * @return bool
     * @throws SegmentException
     */
    public function check(CampaignSegment $campaignSegment, $userId): bool
    {
        if ($this->cacheEnabled) {
            $cacheJob = new CacheSegmentJob($campaignSegment);
            $bloomFilter = Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->get($cacheJob->key());
            if ($bloomFilter) {
                /** @var Bloom $bloomFilter */
                $bloomFilter = unserialize($bloomFilter);
                return $bloomFilter->has($userId);
            }

            dispatch($cacheJob);
        }

        // until the cache is filled, let's check directly
        try {
            $params = [];
            $cso = $campaignSegment->getOverrides();
            if ($cso) {
                $params['fields'] = Json::encode($cso);
            }
            if ($this->cache) {
                $params['cache'] = Json::encode($this->cache);
            }
            $response = $this->client->get(sprintf(self::ENDPOINT_CHECK, $campaignSegment->code, $userId), [
                'query' => $params,
            ]);
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:Check endpoint: {$e->getMessage()}");
        }

        $result = json_decode($response->getBody());
        if ($result->cache) {
            foreach (get_object_vars($result->cache) as $ruleId => $ruleCache) {
                $this->cache->$ruleId = $ruleCache;
            }
        }
        if (isset($result->event_rules)) {
            $this->eventRules = $result->event_rules;
        }
        if (isset($result->overridable_fields)) {
            $this->overridableFields = $result->overridable_fields;
        }

        return $result->check;
    }

    /**
     * @param CampaignSegment $campaignSegment
     * @return Collection
     * @throws SegmentException
     */
    public function users(CampaignSegment $campaignSegment): Collection
    {
        try {
            $response = $this->client->get(sprintf(self::ENDPOINT_USERS, $campaignSegment->code), [
                'query' => [
                    'fields' => Json::encode($campaignSegment->getOverrides())
                ],
            ]);
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:Users endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        $collection = collect($list);
        return $collection;
    }

    public function cacheEnabled(CampaignSegment $campaignSegment): bool
    {
        return $this->cacheEnabled;
    }

    public function setCache($cache): void
    {
        $this->cache = $cache;
    }

    public function getProviderData()
    {
        $pd = new \stdClass();
        if ($this->cache) {
            $pd->cache = $this->cache;
        }
        if ($this->eventRules) {
            $pd->event_rules = $this->eventRules;
        }
        if ($this->overridableFields) {
            $pd->overridable_fields = $this->overridableFields;
        }
        return $pd;
    }
}
