<?php

namespace App\Http\Showtime;

use App\Banner;
use App\Campaign;
use App\CampaignBanner;
use App\CampaignSegment;
use App\Contracts\SegmentAggregator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Predis\ClientInterface;
use Psr\Log\LoggerInterface;

class Showtime
{
    private const BANNER_ONETIME_USER_KEY = 'banner_onetime_user';

    private const BANNER_ONETIME_BROWSER_KEY = 'banner_onetime_browser';

    private const PAGEVIEW_ATTRIBUTE_OPERATOR_IS = '=';

    private const PAGEVIEW_ATTRIBUTE_OPERATOR_IS_NOT = '!=';

    private $request;

    private $positionMap;

    private $dimensionMap;

    private $alignmentsMap;

    private $snippets;

    public function __construct(
        private ClientInterface $redis,
        private SegmentAggregator $segmentAggregator,
        private LazyGeoReader $geoReader,
        private ShowtimeConfig $showtimeConfig,
        private LazyDeviceDetector $deviceDetector,
        private LoggerInterface $logger
    ) {
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    private function getRequest()
    {
        if (!$this->request) {
            $this->request = Request::createFromGlobals();
        }
        return $this->request;
    }

    public function setPositionMap(\App\Models\Position\Map $positions)
    {
        $this->positionMap = $positions->positions();
    }

    public function getShowtimeConfig(): ShowtimeConfig
    {
        return $this->showtimeConfig;
    }

    public function setDimensionMap(\App\Models\Dimension\Map $dimensions)
    {
        $this->dimensionMap = $dimensions->dimensions();
    }

    public function setAlignmentsMap(\App\Models\Alignment\Map $alignments)
    {
        $this->alignmentsMap = $alignments->alignments();
    }

    public function showtime(string $userData, string $callback, ShowtimeResponse $showtimeResponse)
    {
        try {
            $data = json_decode($userData);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('could not decode JSON in Showtime: ' . $userData);
            return $showtimeResponse->error($callback, 400, ['invalid data json provided']);
        }

        $url = $data->url ?? null;
        if (!$url) {
            return $showtimeResponse->error($callback, 400, ['url is required and missing']);
        }

        $userId = null;
        if (isset($data->userId) || !empty($data->userId)) {
            $userId = $data->userId;
        }

        $browserId = null;
        if (isset($data->browserId) || !empty($data->browserId)) {
            $browserId = $data->browserId;
        }
        if (!$browserId) {
            return $showtimeResponse->error($callback, 400, ['browserId is required and missing']);
        }

        // language
        if ($this->showtimeConfig->getAcceptLanguage()) {
            $data->language = $this->showtimeConfig->getAcceptLanguage();
        }

        $segmentAggregator = $this->segmentAggregator;
        if (isset($data->cache)) {
            $segmentAggregator->setProviderData($data->cache);
        }

        $this->loadMaps();

        $positions = $this->positionMap;
        $dimensions = $this->dimensionMap;
        $alignments = $this->alignmentsMap;
        $snippets = $this->snippets;

        $displayData = [];

        if ($this->showtimeConfig->isOneTimeBannerEnabled()) {
            // Try to load one-time banners (having precedence over campaigns)
            $banner = null;
            if ($userId) {
                $banner = $this->loadOneTimeUserBanner($userId);
            }
            if (!$banner) {
                $banner = $this->loadOneTimeBrowserBanner($browserId);
            }
            if ($banner) {
                $displayData[] = $showtimeResponse->renderBanner($banner, $alignments, $dimensions, $positions, $snippets);
                return $showtimeResponse->success($callback, $displayData, [], $segmentAggregator->getProviderData());
            }
        }

        $campaignIds = json_decode($this->redis->get(Campaign::ACTIVE_CAMPAIGN_IDS) ?? '[]') ?? [];
        if (count($campaignIds) === 0) {
            return $showtimeResponse->success($callback, [], [], $segmentAggregator->getProviderData());
        }

        // prepare campaign IDs to fetch
        $dbCampaignIds = [];
        foreach ($campaignIds as $campaignId) {
            $dbCampaignIds[] = Campaign::CAMPAIGN_TAG . ":{$campaignId}";
        }

        // fetch all running campaigns at once
        $fetchedCampaigns = $this->redis->mget($dbCampaignIds);

        $activeCampaigns = [];
        $campaigns = [];
        $campaignBanners = [];
        $suppressedBanners = [];
        reset($campaignIds);
        foreach ($fetchedCampaigns as $fetchedCampaign) {
            /** @var Campaign $campaign */
            $campaign = unserialize($fetchedCampaign, ['allowed_class' => Campaign::class]);
            $campaigns[current($campaignIds)] = $campaign;
            $campaignBanners[] = $this->shouldDisplay($campaign, $data, $activeCampaigns);
            next($campaignIds);
        }

        $campaignBanners = array_filter($campaignBanners);
        if ($this->showtimeConfig->isPrioritizeBannerOnSamePosition()) {
            $campaignBanners = $this->prioritizeCampaignBannerOnPosition($campaigns, $campaignBanners, $activeCampaigns, $suppressedBanners);
        }

        foreach ($campaignBanners as $campaignBanner) {
            $displayData[] = $showtimeResponse->renderCampaign(
                variant: $campaignBanner,
                campaign: $campaigns[$campaignBanner->campaign_id],
                alignments: $alignments,
                dimensions: $dimensions,
                positions: $positions,
                snippets: $snippets,
                userData: $data,
            );
        }

        return $showtimeResponse->success(
            $callback,
            $displayData,
            array_values($activeCampaigns), // make sure $activeCampaigns is always encoded as array in JSON
            $segmentAggregator->getProviderData(),
            $suppressedBanners,
        );
    }

    public function prioritizeCampaignBannerOnPosition(array $campaigns, array $campaignBanners, array &$activeCampaigns, array &$suppressedBanners): array
    {
        $bannersOnPosition = [];
        foreach ($campaignBanners as $campaignBanner) {
            $position = "{$campaignBanner->banner->display_type}_{$campaignBanner->banner->position}_{$campaignBanner->banner->target_selector}";

            if (isset($bannersOnPosition[$position])) {
                /** @var CampaignBanner $bannerOnPosition */
                $bannerOnPosition = $bannersOnPosition[$position];
                /** @var Campaign $campaignOfBannerOnPosition */
                $currentCampaign = $campaigns[$bannerOnPosition->campaign_id];
                /** @var Campaign $newCampaign */
                $newCampaign = $campaigns[$campaignBanner->campaign_id];

                if ($this->hasNewCampaignHigherPriorityOverCurrentOnPosition($newCampaign, $currentCampaign)) {
                    $this->addSuppressedBanner($suppressedBanners, $bannerOnPosition, $currentCampaign->public_id);
                    $bannersOnPosition[$position] = $campaignBanner;
                    $this->removeCampaignByUuid($activeCampaigns, $currentCampaign->uuid);
                } else {
                    $this->addSuppressedBanner($suppressedBanners, $campaignBanner, $newCampaign->public_id);
                    $this->removeCampaignByUuid($activeCampaigns, $newCampaign->uuid);
                }
            } else {
                $bannersOnPosition[$position] = $campaignBanner;
            }
        }

        return array_values($bannersOnPosition);
    }

    private function hasNewCampaignHigherPriorityOverCurrentOnPosition(Campaign $newCampaign, Campaign $currentOnPosition): bool
    {
        // campaign with more banners has higher priority
        if ($newCampaign->campaignBanners->count() > $currentOnPosition->campaignBanners->count()) {
            return true;
        }

        if ($currentOnPosition->campaignBanners->count() === $newCampaign->campaignBanners->count()) {
            // campaign with more recent updates has higher priority
            if ($newCampaign->updated_at > $currentOnPosition->updated_at) {
                return true;
            }
        }

        return false;
    }

    private function removeCampaignByUuid(array &$campaigns, string $uuid): void
    {
        foreach ($campaigns as $key => $campaign) {
            if ($campaign['uuid'] === $uuid) {
                unset($campaigns[$key]);
                return;
            }
        }
    }

    private function addSuppressedBanner(array &$suppressedBanners, $campaignBanner, $campaignPublicId): void
    {
        $suppressedBanners[] = [
            'campaign_banner_public_id' => $campaignBanner->public_id,
            'banner_public_id' => $campaignBanner->banner->public_id,
            'campaign_public_id' => $campaignPublicId
        ];
    }

    /**
     * Determines if campaign should be displayed for user/browser
     * Return either null if campaign should not be displayed or actual variant of CampaignBanner to be displayed
     *
     * @param Campaign    $campaign
     * @param             $userData
     * @param array       $activeCampaigns
     *
     * @return CampaignBanner|null
     */
    public function shouldDisplay(Campaign $campaign, $userData, array &$activeCampaigns): ?CampaignBanner
    {
        $userId = $userData->userId ?? null;
        $browserId = $userData->browserId;
        $running = false;

        foreach ($campaign->schedules as $schedule) {
            if ($schedule->isRunning()) {
                $running = true;
                break;
            }
        }
        if (!$running) {
            return null;
        }

        /** @var Collection $campaignBanners */
        $campaignBanners = $campaign->campaignBanners->keyBy('uuid');

        // banner
        if ($campaignBanners->count() == 0) {
            $this->logger->error("Active campaign [{$campaign->uuid}] has no banner set");
            return null;
        }

        $bannerUuid = null;
        $variantUuid = null;

        // find variant previously displayed to user
        $seenCampaigns = $userData->campaigns ?? false;
        if ($seenCampaigns) {
            if (isset($seenCampaigns->{$campaign->uuid})) {
                $bannerUuid = $seenCampaigns->{$campaign->uuid}->bannerId ?? null;
                $variantUuid = $seenCampaigns->{$campaign->uuid}->variantId ?? null;
            }

            if (isset($seenCampaigns->{$campaign->public_id}->variantId)) {
                foreach ($campaign->campaignBanners as $campaignBanner) {
                    if ($campaignBanner->public_id === $seenCampaigns->{$campaign->public_id}->variantId) {
                        $bannerUuid = $campaignBanner->banner->uuid ?? null;
                        $variantUuid = $campaignBanner->uuid ?? null;
                        break;
                    }
                }
            }
        }

        // fallback for older version of campaigns local storage data
        // where decision was based on bannerUuid and not variantUuid (which was not present at all)
        if ($bannerUuid && !$variantUuid) {
            foreach ($campaignBanners as $campaignBanner) {
                if (optional($campaignBanner->banner)->uuid === $bannerUuid) {
                    $variantUuid = $campaignBanner->uuid;
                    break;
                }
            }
        }

        /** @var CampaignBanner $seenVariant */
        // unset seen variant if it was deleted
        if (!($seenVariant = $campaignBanners->get($variantUuid))) {
            $variantUuid = null;
        }

        // unset seen variant if its proportion is 0%
        if ($seenVariant && $seenVariant->proportion === 0) {
            $variantUuid = null;
        }

        // variant still not set, choose random variant
        if ($variantUuid === null) {
            $variantsMapping = $campaign->getVariantsProportionMapping();

            $randVal = mt_rand(0, 100);
            $currPercent = 0;

            foreach ($variantsMapping as $uuid => $proportion) {
                $currPercent = $currPercent + $proportion;
                if ($currPercent >= $randVal) {
                    $variantUuid = $uuid;
                    break;
                }
            }
        }

        /** @var CampaignBanner $variant */
        $variant = $campaignBanners->get($variantUuid);
        if (!$variant) {
            $this->logger->error("Unable to get CampaignBanner [{$variantUuid}] for campaign [{$campaign->uuid}]");
            return null;
        }

        // check if campaign is set to be seen only once per session
        $campaignsSeenInSession = $userData->campaignsSession ?? [];
        if ($campaign->once_per_session && $campaignsSeenInSession) {
            $seen = isset($campaignsSeenInSession->{$campaign->uuid});
            if ($seen) {
                return null;
            }
        }

        // signed in state
        if (isset($campaign->signed_in) && $campaign->signed_in !== (bool) $userId) {
            return null;
        }

        // using adblock?
        if ($campaign->using_adblock !== null) {
            if (!isset($userData->usingAdblock)) {
                $this->logger->error("Unable to load if user with ID [{$userId}] & browserId [{$browserId}] is using AdBlock.");
                return null;
            }
            if (($campaign->using_adblock && !$userData->usingAdblock) || ($campaign->using_adblock === false && $userData->usingAdblock)) {
                return null;
            }
        }

        // url filters
        if ($campaign->url_filter === Campaign::URL_FILTER_EXCEPT_AT) {
            foreach ($campaign->url_patterns as $urlPattern) {
                if (strpos($userData->url, $urlPattern) !== false) {
                    return null;
                }
            }
        }
        if ($campaign->url_filter === Campaign::URL_FILTER_ONLY_AT) {
            $matched = false;
            foreach ($campaign->url_patterns as $urlPattern) {
                if (strpos($userData->url, $urlPattern) !== false) {
                    $matched = true;
                }
            }
            if (!$matched) {
                return null;
            }
        }

        // referer filters
        if ($campaign->referer_filter === Campaign::URL_FILTER_EXCEPT_AT && $userData->referer) {
            foreach ($campaign->referer_patterns as $refererPattern) {
                if (strpos($userData->referer, $refererPattern) !== false) {
                    return null;
                }
            }
        }
        if ($campaign->referer_filter === Campaign::URL_FILTER_ONLY_AT) {
            if (!$userData->referer) {
                return null;
            }
            $matched = false;
            foreach ($campaign->referer_patterns as $refererPattern) {
                if (strpos($userData->referer, $refererPattern) !== false) {
                    $matched = true;
                }
            }
            if (!$matched) {
                return null;
            }
        }

        // device rules
        if (!isset($userData->userAgent)) {
            $this->logger->error("Unable to load user agent for userId [{$userId}]");
        } else if (in_array(Campaign::DEVICE_MOBILE, $campaign->devices, true)
            || in_array(Campaign::DEVICE_DESKTOP, $campaign->devices, true)
        ) {
            // parse user agent
            $deviceDetector = $this->deviceDetector->get($userData->userAgent);

            if (!in_array(Campaign::DEVICE_MOBILE, $campaign->devices, true) && $deviceDetector->isMobile()) {
                return null;
            }

            if (!in_array(Campaign::DEVICE_DESKTOP, $campaign->devices, true) && $deviceDetector->isDesktop()) {
                return null;
            }
        }

        // country rules
        if (!$campaign->countries->isEmpty()) {
            // load country ISO code based on IP
            try {
                $countryCode = $this->geoReader->countryCode($this->getRequest()->ip());
                if ($countryCode === null) {
                    $this->logger->debug("Unable to identify country for campaign '{$campaign->id}'.");
                    return null;
                }
            } catch (\MaxMind\Db\Reader\InvalidDatabaseException $e) {
                $this->logger->error("Unable to identify country for campaign '{$campaign->id}': " . $e->getMessage());
                return null;
            } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
                $this->logger->debug("Unable to identify country for campaign '{$campaign->id}': " . $e->getMessage());
                return null;
            }

            // check against white / black listed countries

            if (!$campaign->countriesBlacklist->isEmpty() && $campaign->countriesBlacklist->contains('iso_code', $countryCode)) {
                return null;
            }
            if (!$campaign->countriesWhitelist->isEmpty() && !$campaign->countriesWhitelist->contains('iso_code', $countryCode)) {
                return null;
            }
        }

        // languages rules
        if (!empty($userData->language)) {
            $userData->primaryLanguage = \Locale::getPrimaryLanguage($userData->language);
        }
        if (!empty($campaign->languages)) {
            if (empty($userData->primaryLanguage) || !in_array($userData->primaryLanguage, $campaign->languages, true)) {
                return null;
            }
        }

        // segment
        $segmentRulesOk = $this->evaluateSegmentRules($campaign, $browserId, $userId);
        if (!$segmentRulesOk) {
            return null;
        }

        // Active campaign is campaign that targets selected user (previous rules were passed),
        // but whether it displays or not depends on pageview counting rules (every n-th page, up to N pageviews).
        // We need to track such campaigns on the client-side too.
        $activeCampaigns[] = ['uuid' => $campaign->uuid, 'public_id' => $campaign->public_id];

        $seenCampaign = $seenCampaigns->{$campaign->uuid} ?? $seenCampaigns->{$campaign->public_id} ?? null;

        // pageview rules - check display banner every n-th request
        if ($seenCampaign !== null && $campaign->pageview_rules !== null) {
            $pageviewCount = $seenCampaign->count ?? null;

            if ($pageviewCount === null) {
                // if campaign is recorder as seen but has no pageview count,
                // it means there is a probably old version or remplib cached on the client
                // do not show campaign (browser should reload the library)
                return null;
            }

            $displayBanner = $campaign->pageview_rules['display_banner'] ?? null;
            $displayBannerEvery = $campaign->pageview_rules['display_banner_every'] ?? 1;
            if ($displayBanner === 'every' && $pageviewCount % $displayBannerEvery !== 0) {
                return null;
            }

            $sessionCampaign = $campaignsSeenInSession->{$campaign->uuid} ?? $campaignsSeenInSession->{$campaign->public_id} ?? null;

            if (property_exists($seenCampaign, 'closedAt')) {
                $afterClosedRule = $campaign->pageview_rules['after_banner_closed_display'] ?? null;
                $afterClosedHours = $campaign->pageview_rules['after_closed_hours'] ?? 0;
                if ($afterClosedRule === 'never') {
                    return null;
                }

                if ($afterClosedRule === 'never_in_session' && isset($sessionCampaign->closedAt)) {
                    return null;
                }

                if ($afterClosedRule === 'close_for_hours' && $afterClosedHours) {
                    $threshold = time() - $afterClosedHours * 60 * 60;
                    if ($seenCampaign->closedAt > $threshold) {
                        return null;
                    }
                }
            }

            if (property_exists($seenCampaign, 'clickedAt')) {
                $afterClickedRule = $campaign->pageview_rules['after_banner_clicked_display'] ?? null;
                $afterClickedHours = $campaign->pageview_rules['after_clicked_hours'] ?? 0;
                if ($afterClickedRule === 'never') {
                    return null;
                }
                if ($afterClickedRule === 'never_in_session' && isset($sessionCampaign->clickedAt)) {
                    return null;
                }

                if ($afterClickedRule === 'close_for_hours' && $afterClickedHours) {
                    $threshold = time() - $afterClickedHours * 60 * 60;
                    if ($seenCampaign->clickedAt > $threshold) {
                        return null;
                    }
                }
            }
        }

        // pageview attributes - check if sent pageview attributes match conditions
        if (!empty($campaign->pageview_attributes)) {
            if (empty($userData->pageviewAttributes)) {
                return null;
            }

            foreach ($campaign->pageview_attributes as $attribute) {
                $attrName = $attribute['name'];
                $attrValue = $attribute['value'];
                $attrOperator = $attribute['operator'];

                if ($attrOperator === self::PAGEVIEW_ATTRIBUTE_OPERATOR_IS) {
                    if (!property_exists($userData->pageviewAttributes, $attrName)) {
                        return null;
                    }
                    if (is_array($userData->pageviewAttributes->{$attrName})) {
                        if (!in_array($attrValue, $userData->pageviewAttributes->{$attrName})) {
                            return null;
                        }
                    } elseif ($userData->pageviewAttributes->{$attrName} !== $attrValue) {
                        return null;
                    }
                }

                if ($attrOperator === self::PAGEVIEW_ATTRIBUTE_OPERATOR_IS_NOT) {
                    if (property_exists($userData->pageviewAttributes, $attrName)) {
                        if (is_array($userData->pageviewAttributes->{$attrName})) {
                            if (in_array($attrValue, $userData->pageviewAttributes->{$attrName})) {
                                return null;
                            }
                        } else {
                            if ($userData->pageviewAttributes->{$attrName} === $attrValue) {
                                return null;
                            }
                        }
                    }
                }
            }
        }

        // seen count rules
        if ($seenCampaign !== null && $campaign->pageview_rules !== null) {
            $seenCount = $seenCampaign->seen ?? null;

            if ($seenCount === null) {
                // if campaign is recorder as seen but has no pageview count,
                // it means there is a probably old version or remplib cached on the client
                // do not show campaign (browser should reload the library)
                return null;
            }

            $displayTimes = $campaign->pageview_rules['display_times'] ?? null;
            if ($displayTimes && $seenCount >= $campaign->pageview_rules['display_n_times']) {
                return null;
            }
        }

        return $variant;
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

    private function loadMaps(): void
    {
        $keys = [];
        if (!$this->positionMap) {
            $keys[] = \App\Models\Position\Map::POSITIONS_MAP_REDIS_KEY;
        }

        if (!$this->dimensionMap) {
            $keys[] = \App\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY;
        }

        if (!$this->alignmentsMap) {
            $keys[] = \App\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY;
        }

        if (!$this->snippets) {
            $keys[] = \App\Snippet::REDIS_CACHE_KEY;
        }

        $maps = $this->redis->mget($keys);
        reset($keys);
        foreach ($maps as $map) {
            $key = current($keys);

            switch ($key) {
                case \App\Models\Position\Map::POSITIONS_MAP_REDIS_KEY:
                    $this->positionMap = json_decode($map, true) ?? [];
                    break;
                case \App\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY:
                    $this->dimensionMap = json_decode($map, true) ?? [];
                    break;
                case \App\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY:
                    $this->alignmentsMap = json_decode($map, true) ?? [];
                    break;
                case \App\Snippet::REDIS_CACHE_KEY:
                    $this->snippets = json_decode($map, true) ?? [];
                    break;
            }
            next($keys);
        }
    }

    private function loadOneTimeUserBanner($userId): ?Banner
    {
        $userBannerKeys = [];
        foreach ($this->redis->keys(self::BANNER_ONETIME_USER_KEY . ":$userId:*") as $userBannerKey) {
            $parts = explode(':', $userBannerKey, 3);
            $userBannerKeys[$parts[2]] = $userBannerKey;
        }

        return $this->loadOneTimeBanner($userBannerKeys);
    }

    private function loadOneTimeBrowserBanner($browserId): ?Banner
    {
        $browserBannerKeys = [];
        foreach ($this->redis->keys(self::BANNER_ONETIME_BROWSER_KEY . ":$browserId:*") as $browserBannerKey) {
            $parts = explode(':', $browserBannerKey, 3);
            $browserBannerKeys[$parts[2]] = $browserBannerKey;
        }

        return $this->loadOneTimeBanner($browserBannerKeys);
    }

    private function evaluateSegmentRules(Campaign $campaign, $browserId, $userId = null)
    {
        if ($campaign->segments->isEmpty()) {
            return true;
        }

        foreach ($campaign->segments as $campaignSegment) {
            $campaignSegment->setRelation('campaign', $campaign); // setting this manually to avoid DB query

            if ($userId) {
                if (!$this->isCacheValid($campaignSegment)) {
                    return false;
                }

                $belongsToSegment = $this->segmentAggregator->checkUser($campaignSegment, (string)$userId);
            } else {
                $belongsToSegment = $this->segmentAggregator->checkBrowser($campaignSegment, (string)$browserId);
            }

            // user is member of segment, that's excluded from campaign; halt execution
            if ($belongsToSegment && !$campaignSegment->inclusive) {
                return false;
            }
            // user is NOT member of segment, that's required for campaign; halt execution
            if (!$belongsToSegment && $campaignSegment->inclusive) {
                return false;
            }
        }

        return true;
    }

    private function isCacheValid(CampaignSegment $campaignSegment): bool
    {
        if (!$this->segmentAggregator->cacheEnabled($campaignSegment)) {
            return true;
        }

        $cacheKeyTimeStamp = $this->redis->get(SegmentAggregator::cacheKey($campaignSegment) . '|timestamp');
        if ($cacheKeyTimeStamp) {
            return true;
        }

        return false;
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
