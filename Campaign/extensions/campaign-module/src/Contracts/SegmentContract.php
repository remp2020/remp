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
}
