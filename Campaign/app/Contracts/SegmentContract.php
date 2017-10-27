<?php

namespace App\Contracts;

use App\CampaignSegment;
use Illuminate\Support\Collection;

interface SegmentContract
{
    const BLOOM_FILTER_CACHE_TAG = 'segment_bloom';

    public function list(): Collection;

    public function check(CampaignSegment $campaignSegment, $userId, array $overrides): bool;

    public function users(CampaignSegment $campaignSegment, array $overrides): Collection;

    public function provider(): string;
}
