<?php

namespace App\Contracts\Remp;

use App\CampaignSegment;
use App\Contracts\SegmentContract;
use App\Contracts\SegmentException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;

class Segment implements SegmentContract
{
    const PROVIDER_ALIAS = 'remp_segment';

    const ENDPOINT_LIST = 'segments';

    const ENDPOINT_CHECK = 'segments/%s/check/%s';

    const ENDPOINT_USERS = 'segments/%s/users';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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
     * @return bool
     * @throws SegmentException
     */
    public function check(CampaignSegment $campaignSegment, $userId): bool
    {
//        $bloomFilter = Cache::tags([SegmentContract::BLOOM_FILTER_CACHE_TAG])->get($segmentId);
//        if ($bloomFilter) {
//            /** @var Bloom $bloomFilter */
//            $bloomFilter = unserialize($bloomFilter);
//            return $bloomFilter->has($userId);
//        }
//        dispatch(new CacheSegmentJob($segmentId));

        try {
            $response = $this->client->get(sprintf(self::ENDPOINT_CHECK, $campaignSegment->code, $userId));
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:Check endpoint: {$e->getMessage()}");
        }

        $result = json_decode($response->getBody());
        return $result->check;
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
