<?php

namespace Remp\CampaignModule\Contracts;

use Remp\CampaignModule\CampaignSegment;
use Illuminate\Support\Collection;

interface SegmentContract
{
    const CACHE_TAG = 'segment';

    public function list(): Collection;

    public function checkUser(CampaignSegment $campaignSegment, string $userId): bool;

    public function checkBrowser(CampaignSegment $campaignSegment, string $browserId): bool;

    public function users(CampaignSegment $campaignSegment): Collection;

    public function provider(): string;

    public function cacheEnabled(CampaignSegment $campaignSegment): bool;

    public function addUserToCache(CampaignSegment $campaignSegment, string $userId): bool;

    public function removeUserFromCache(CampaignSegment $campaignSegment, string $userId): bool;

    /**
     * setCache stores and provides cache object for campaign segment providers.
     *
     * @param $cache \stdClass Array of objects keyed by name of the segment provider. Internals
     *                     of the stored object is defined by contract of provider itself
     *                     and not subject of validation here.
     */
    public function setProviderData($cache): void;

    /**
     * getProviderData returns internal per-provider data objects to be stored
     * by third party and possibly provided later via *setCache()* call.
     *
     * @return \stdClass
     */
    public function getProviderData();
}
