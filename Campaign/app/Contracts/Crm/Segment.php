<?php

namespace App\Contracts\Crm;

use App\Contracts\SegmentContract;
use App\Jobs\CacheSegmentJob;
use Cache;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Razorpay\BloomFilter\Bloom;

class Segment implements SegmentContract
{
    const ENDPOINT_LIST = 'user-segments/list';

    const ENDPOINT_CHECK = 'user-segments/check';

    const ENDPOINT_USERS = 'user-segments/users';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(): Collection
    {
        $response = $this->client->get(self::ENDPOINT_LIST);
        $list = json_decode($response->getBody());
        $collection = collect($list->segments);
        return $collection;
    }

    public function check($segmentId, $userId): bool
    {
        $bloomFilter = Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->get($segmentId);
        if (!$bloomFilter) {
            dispatch(new CacheSegmentJob($segmentId));

            $response = $this->client->get(self::ENDPOINT_CHECK, [
                'query' => [
                    'resolver_type' => 'email',
                    'resolver_value' => $userId,
                    'code' => $segmentId,
                ],
            ]);
            $result = json_decode($response->getBody());
            return $result->check;
        }

        /** @var Bloom $bloomFilter */
        $bloomFilter = unserialize($bloomFilter);
        return $bloomFilter->has($userId);
    }

    public function users($segmentId): Collection
    {
        $response = $this->client->get(self::ENDPOINT_USERS, [
            'query' => [
                'code' => $segmentId,
            ],
        ]);
        $list = json_decode($response->getBody());
        $collection = collect($list->users);
        return $collection;
    }
}