<?php

namespace Remp\CampaignModule\Contracts;

use Remp\CampaignModule\CampaignSegment;

/**
 * Segment provider able to check user's membership in multiple cached segments at once (in a single round trip
 * to Redis). The check has to be cheap enough to account for possibility that some of the campaigns wouldn't be
 * checked otherwise (due to early return related to other rules).
 */
interface SegmentCacheBatchContract
{
    /**
     * Checks user's membership in the cache of each segment.
     *
     * @param CampaignSegment[] $campaignSegments
     * @return bool[] membership in each segment with populated cache, keys of $campaignSegments are preserved
     */
    public function batchCheckUserInCache(array $campaignSegments, string $userId): array;
}
