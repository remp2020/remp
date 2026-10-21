<?php

namespace Remp\CampaignModule\Contracts\Crm;

use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Contracts\SegmentContract;
use Remp\CampaignModule\Contracts\SegmentException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;
use Predis\ClientInterface;

class Segment implements SegmentContract
{
    const PROVIDER_ALIAS = 'crm_segment';

    const ENDPOINT_LIST = 'user-segments/list';

    const ENDPOINT_CHECK = 'user-segments/check';

    const ENDPOINT_USERS = 'user-segments/users';

    private $client;

    private $redis;

    public function __construct(Client $client, ClientInterface|\Redis $redis)
    {
        $this->client = $client;
        $this->redis = $redis;
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
        foreach ($list->segments as $item) {
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
     * @param string $userId
     * @return bool
     */
    public function checkUser(CampaignSegment $campaignSegment, string $userId): bool
    {
        return $this->redis->sismember(SegmentAggregator::cacheKey($campaignSegment), $userId);
    }

    /**
     * @param CampaignSegment $campaignSegment
     * @param string $browserId
     * @return bool
     */
    public function checkBrowser(CampaignSegment $campaignSegment, string $browserId): bool
    {
        // CRM segments don't support browser tracking
        return false;
    }

    /**
     * @param CampaignSegment $campaignSegment
     * @return Collection
     * @throws SegmentException
     */
    public function users(CampaignSegment $campaignSegment): Collection
    {
        try {
            $response = $this->client->get(self::ENDPOINT_USERS, [
                'query' => [
                    'code' => $campaignSegment->code,
                ],
            ]);
        } catch (ConnectException $e) {
            throw new SegmentException("Could not connect to Segment:Check endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        $userIds = array_map(function ($item) {
            return $item->id;
        }, $list->users);

        return collect($userIds);
    }

    public function cacheEnabled(CampaignSegment $campaignSegment): bool
    {
        return true;
    }

    public function addUserToCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        return $this->redis->sadd(
            SegmentAggregator::cacheKey($campaignSegment),
            $this->redis instanceof \Redis ? $userId : [$userId]
        ) ?: false;
    }

    public function removeUserFromCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        return $this->redis->srem(
            SegmentAggregator::cacheKey($campaignSegment),
            $this->redis instanceof \Redis ? $userId : [$userId]
        ) ?: false;
    }
}
