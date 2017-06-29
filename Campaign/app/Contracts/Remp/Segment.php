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
    const ALIAS = 'remp_segment';

    const ENDPOINT_LIST = 'segments/list';

    const ENDPOINT_CHECK = 'segments/check/%s/user/%s';

    const ENDPOINT_USERS = 'segments/%s/users';

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
        $campaignSegments = [];
        foreach ($list as $item) {
            $cs = new CampaignSegment();
            $cs->name = $item->name;
            $cs->provider = self::ALIAS;
            $cs->code = $item->code;
            $cs->group = $item->group;
            $campaignSegments[] = $cs;
        }
        $collection = collect($campaignSegments);
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
        return true;
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