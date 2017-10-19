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
use Razorpay\BloomFilter\Bloom;

class Segment implements SegmentContract
{
    const PROVIDER_ALIAS = 'remp_segment';

    const ENDPOINT_LIST = 'segments';

    const ENDPOINT_CHECK = 'segments/%s/check/%s';

    const ENDPOINT_USERS = 'segments/%s/users';

    private $client;

    private $allowCache;

    public function __construct(Client $client, $allowCache)
    {
        $this->client = $client;
        $this->allowCache = false;
    }

    public function provider(): string
    {
        return self::PROVIDER_ALIAS;
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
     * @param array $overrides
     * @return bool
     * @throws SegmentException
     */
    public function check(CampaignSegment $campaignSegment, $userId, array $overrides): bool
    {
        if ($this->allowCache) {
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
            $response = $this->client->post(sprintf(self::ENDPOINT_CHECK, $campaignSegment->code, $userId), [
                'json' => [
                    'fields' => $overrides,
                ],
            ]);
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:Check endpoint: {$e->getMessage()}");
        }

        $result = json_decode($response->getBody());
        return $result->check;
    }

    /**
     * @param CampaignSegment $campaignSegment
     * @param array $overrides
     * @return Collection
     * @throws SegmentException
     */
    public function users(CampaignSegment $campaignSegment, array $overrides): Collection
    {
        try {
            $response = $this->client->post(sprintf(self::ENDPOINT_USERS, $campaignSegment->code), [
                'json' => $overrides,
            ]);
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:Users endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        $collection = collect($list);
        return $collection;
    }
}
