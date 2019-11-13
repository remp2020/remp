<?php

namespace App\Helpers;

use App\Banner;
use App\Campaign;
use App\Contracts\SegmentAggregator;
use Carbon\Carbon;
use Predis\ClientInterface;

class Showtime
{
    private const BANNER_ONETIME_USER_KEY = 'banner_onetime_user';
    private const BANNER_ONETIME_BROWSER_KEY = 'banner_onetime_browser';

    private $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function displayForUser(Banner $banner, string $userId, int $expiresInSeconds)
    {
        $timestamp = Carbon::now()->getTimestamp();
        $key = self::BANNER_ONETIME_USER_KEY . ":$userId:$timestamp";

        $this->redis->rpush($key, $banner->id);
        $this->redis->expire($key, $expiresInSeconds);
    }

    public function displayForBrowser(Banner $banner, string $browserId, int $expiresInSeconds)
    {
        $timestamp = Carbon::now()->getTimestamp();
        $key = self::BANNER_ONETIME_BROWSER_KEY . ":$browserId:$timestamp";

        $this->redis->rpush($key, $banner->id);
        $this->redis->expire($key, $expiresInSeconds);
    }

    public function loadOneTimeUserBanner($userId): ?Banner
    {
        $userBannerKeys = [];
        foreach ($this->redis->keys(self::BANNER_ONETIME_USER_KEY . ":$userId:*") as $userBannerKey) {
            $parts = explode(':', $userBannerKey, 3);
            $userBannerKeys[$parts[2]] = $userBannerKey;
        }

        return $this->loadOneTimeBanner($userBannerKeys);
    }

    public function loadOneTimeBrowserBanner($browserId): ?Banner
    {
        $browserBannerKeys = [];
        foreach ($this->redis->keys(self::BANNER_ONETIME_BROWSER_KEY . ":$browserId:*") as $browserBannerKey) {
            $parts = explode(':', $browserBannerKey, 3);
            $browserBannerKeys[$parts[2]] = $browserBannerKey;
        }

        return $this->loadOneTimeBanner($browserBannerKeys);
    }

    public function canSegmentSeeTheCampaign(Campaign $campaign, SegmentAggregator $segmentAggregator, $userId, $browserId) {
        if ($campaign->segments->isEmpty()) {
            return true;
        }

        foreach ($campaign->segments as $campaignSegment) {
            $campaignSegment->setRelation('campaign', $campaign); // setting this manually to avoid DB query

            if ($userId) {
                $belongsToSegment = $segmentAggregator->checkUser($campaignSegment, strval($userId));
            } else {
                $belongsToSegment = $segmentAggregator->checkBrowser($campaignSegment, strval($browserId));
            }
            
            return $this->canUserSeeTheCampaign($belongsToSegment, $campaignSegment->inclusive);
        }
    }

    private function canUserSeeTheCampaign($userBelongsToSegment, $segmentIsInclusive) {
        $canSeeTheCampaign =
            ($segmentIsInclusive && $userBelongsToSegment) ||
            (!$segmentIsInclusive && !$userBelongsToSegment);
        return $canSeeTheCampaign;
    }

    private function loadOneTimeBanner(array $bannerKeys): ?Banner
    {
        // Banner keys have format BANNER_TAG:USER_ID/BROWSER_ID:TIMESTAMP
        // Try to display the earliest banner first, therefore sort banner keys here (indexed by TIMESTAMP)
        ksort($bannerKeys);

        foreach ($bannerKeys as $bannerKey) {
            $bannerId = $this->redis->lpop($bannerKey);
            if (!empty($bannerId)) {
                $banner = Banner::loadCachedBanner($this->redis, $bannerId);
                if (!$banner) {
                    throw new \Exception("Banner with ID $bannerId is not present in cache");
                }
                return $banner;
            }
        }
        return null;
    }
}
