<?php

namespace App\Contracts\Crm;

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
    const ENDPOINT_LIST = 'user-segments/list';

    const ENDPOINT_CHECK = 'user-segments/check';

    const ENDPOINT_USERS = 'user-segments/users';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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
        $collection = collect($list->segments);
        return $collection;
    }

    /**
     * @param $segmentId
     * @param $userId
     * @return bool
     * @throws SegmentException
     */
    public function check($segmentId, $userId): bool
    {
        $bloomFilter = Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->get($segmentId);
        if (!$bloomFilter) {
            dispatch(new CacheSegmentJob($segmentId));

            try {
                $response = $this->client->get(self::ENDPOINT_CHECK, [
                    'query' => [
                        'resolver_type' => 'email',
                        'resolver_value' => $userId,
                        'code' => $segmentId,
                    ],
                ]);
            } catch (ConnectException $e) {
                throw new SegmentException("Could not connect to Segment:Check endpoint: {$e->getMessage()}");
            }

            $result = json_decode($response->getBody());
            return $result->check;
        }

        /** @var Bloom $bloomFilter */
        $bloomFilter = unserialize($bloomFilter);
        return $bloomFilter->has($userId);
    }

    /**
     * @param $segmentId
     * @return Collection
     * @throws SegmentException
     */
    public function users($segmentId): Collection
    {
        try {
            $response = $this->client->get(self::ENDPOINT_USERS, [
                'query' => [
                    'code' => $segmentId,
                ],
            ]);
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:Check endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        $collection = collect($list->users);
        return $collection;
    }
}