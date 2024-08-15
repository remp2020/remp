<?php

namespace Remp\CampaignModule\Contracts\Pythia;

use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\SegmentContract;
use Remp\CampaignModule\Contracts\SegmentException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Segment implements SegmentContract
{
    const PROVIDER_ALIAS = 'pythia_segment';

    const ENDPOINT_LIST = 'segments';

    const ENDPOINT_USERS_CHECK = 'segments/%s/users/check/%s';

    const ENDPOINT_BROWSERS_CHECK = 'segments/%s/browsers/check/%s';

    const ENDPOINT_USERS = 'segments/%s/users';

    private $client;

    private $cache;


    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->cache = new \stdClass;
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
    public function checkUser(CampaignSegment $campaignSegment, string $userId): bool
    {
        return $this->check($campaignSegment, self::ENDPOINT_USERS_CHECK, $userId);
    }

    /**
     * @param CampaignSegment $campaignSegment
     * @param string $browserId
     * @return bool
     * @throws SegmentException
     */
    public function checkBrowser(CampaignSegment $campaignSegment, string $browserId): bool
    {
        return $this->check($campaignSegment, self::ENDPOINT_BROWSERS_CHECK, $browserId);
    }

    /**
     * @param CampaignSegment $campaignSegment
     * @param string $endpoint
     * @param string $checkedId
     *
     * @return mixed
     * @throws SegmentException
     */
    private function check(CampaignSegment $campaignSegment, $endpoint, $checkedId)
    {
        try {
            $params = [];
            $response = $this->client->get(sprintf($endpoint, $campaignSegment->code, $checkedId), [
                'query' => $params,
            ]);
        } catch (ConnectException $e) {
            Log::warning("Could not connect to Segment:Check endpoint: {$e->getMessage()}");
            return false;
        }

        $result = json_decode($response->getBody());
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
                    'fields' => json_encode($campaignSegment->getOverrides())
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
        return false;
    }

    public function addUserToCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        return false;
    }

    public function removeUserFromCache(CampaignSegment $campaignSegment, string $userId): bool
    {
        return false;
    }

    public function setProviderData($cache): void
    {
        $this->cache = $cache;
    }

    public function getProviderData()
    {
        return $this->cache;
    }
}
