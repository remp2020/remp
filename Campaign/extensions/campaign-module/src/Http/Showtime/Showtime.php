<?php

namespace Remp\CampaignModule\Http\Showtime;

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\SegmentAggregator;
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
    private $colorSchemesMap;
    private array $snippets = [];
    private array $localCache = [];

    public function __construct(
        private ClientInterface|\Redis $redis,
        private SegmentAggregator $segmentAggregator,
        private LazyGeoReader $geoReader,
        private ShowtimeConfig $showtimeConfig,
        private DeviceRulesEvaluator $deviceRulesEvaluator,
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

    public function setPositionMap(\Remp\CampaignModule\Models\Position\Map $positions)
    {
        $this->positionMap = $positions->positions();
    }

    public function getShowtimeConfig(): ShowtimeConfig
    {
        return $this->showtimeConfig;
    }

    public function setDimensionMap(\Remp\CampaignModule\Models\Dimension\Map $dimensions)
    {
        $this->dimensionMap = $dimensions->dimensions();
    }

    public function setAlignmentsMap(\Remp\CampaignModule\Models\Alignment\Map $alignments)
    {
        $this->alignmentsMap = $alignments->alignments();
    }

    public function setColorSchemesMap(\Remp\CampaignModule\Models\ColorScheme\Map $colorSchemes)
    {
        $this->colorSchemesMap = $colorSchemes->colorSchemes();
    }

    public function flushLocalCache(): self
    {
        $this->localCache = [];
        $this->deviceRulesEvaluator->flushLocalCache();
        return $this;
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

        if (!property_exists($data, 'referer')) {
            // see https://github.com/remp2020/remp/blob/ae5196826988a7df2d9a0b5ca64d1823c9f236d7/Campaign/extensions/campaign-module/resources/assets/js/remplib.js#L46
            return $showtimeResponse->error($callback, 400, ['referer is required and missing (null is acceptable)']);
        }

        // language
        if ($this->showtimeConfig->getAcceptLanguage()) {
            $data->language = $this->showtimeConfig->getAcceptLanguage();
        }

        // debugger
        $evaluationMessages = [];
        $debugCampaignPublicId = null;
        $debug = false;
        if (isset($data->debug->key)) {
            if ($data->debug->key === $this->showtimeConfig->getDebugKey()) {
                $debug = true;
                $debugCampaignPublicId = $data->debug->campaignPublicId ?? null;

                if (isset($data->debug->userId)) {
                    $data->userId = $data->debug->userId;
                    $userId = $data->debug->userId;
                }

                if (isset($data->debug->referer)) {
                    $data->referer = $data->debug->referer;
                }
            } else {
                $evaluationMessages[] = 'Invalid debug key';
            }
        }

        $segmentAggregator = $this->segmentAggregator;
        if (isset($data->cache)) {
            $segmentAggregator->setProviderData($data->cache);
        }

        $this->loadMaps();

        $positions = $this->positionMap;
        $dimensions = $this->dimensionMap;
        $alignments = $this->alignmentsMap;
        $colorSchemes = $this->colorSchemesMap;
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
                $displayData[] = $showtimeResponse->renderBanner(
                    banner: $banner,
                    alignments: $alignments,
                    dimensions: $dimensions,
                    positions: $positions,
                    colorSchemes: $colorSchemes,
                    snippets: $snippets,
                );
                return $showtimeResponse->success(
                    callback: $callback,
                    data: $displayData,
                    activeCampaigns: [],
                    providerData: $segmentAggregator->getProviderData(),
                    suppressedBanners: [],
                    evaluationMessages: $evaluationMessages,
                );
            }
        }

        $campaignIds = json_decode($this->redis->get(Campaign::ACTIVE_CAMPAIGN_IDS) ?? '[]') ?? [];
        if (count($campaignIds) === 0) {
            return $showtimeResponse->success(
                callback: $callback,
                data: [],
                activeCampaigns: [],
                providerData: $segmentAggregator->getProviderData(),
                suppressedBanners: [],
                evaluationMessages: $evaluationMessages,
            );
        }

        // prepare campaign IDs to fetch
        $cachedCampaignKeys = [];
        $cachedCampaignSnippetCodesKeys = [];
        foreach ($campaignIds as $campaignId) {
            $cachedCampaignKeys[] = Campaign::CAMPAIGN_JSON_TAG . ":{$campaignId}";
            $cachedCampaignSnippetCodesKeys[] = Campaign::CAMPAIGN_SNIPPET_CODES_JSON_TAG . ":{$campaignId}";
        }

        // fetch all running campaigns at once
        $fetchedCampaigns = $this->redis->mget($cachedCampaignKeys);

        // fetch all possibly active snippets
        $fetchedCampaignSnippets = $this->redis->mget($cachedCampaignSnippetCodesKeys);
        $campaignBannerSnippets = [];
        foreach ($fetchedCampaignSnippets as $fetchedCampaignSnippet) {
            $campaignBannerSnippets = array_merge(
                $campaignBannerSnippets,
                json_decode($fetchedCampaignSnippet, associative: true),
            );
        }

        $activeCampaigns = [];
        $campaigns = [];
        $campaignBanners = [];
        $debugCampaignId = null;
        $suppressedBanners = [];
        $this->flushLocalCache();

        foreach ($fetchedCampaigns as $fetchedCampaign) {
            /** @var Campaign $campaign */
            $campaign = new Campaign();
            $campaign->hydrateFromCache(json_decode($fetchedCampaign, true));
            $campaigns[$campaign->id] = $campaign;
            if ($campaign->public_id === $debugCampaignPublicId) {
                $debugCampaignId = $campaign->id;
            }

            $result = $this->campaignBannerToDisplay(
                campaign: $campaign,
                userData: $data,
                activeCampaigns: $activeCampaigns,
            );
            if ($result instanceof CampaignBanner) {
                $campaignBanners[] = $result;
            } else if ($debug && $campaign->id === $debugCampaignId) {
                $evaluationMessages[] = $result;
            }
        }

        $campaignBanners = array_filter($campaignBanners);
        if ($this->showtimeConfig->isPrioritizeBannerOnSamePosition()) {
            $campaignBanners = $this->prioritizeCampaignBannerOnPosition($campaigns, $campaignBanners, $activeCampaigns, $suppressedBanners);
        }

        foreach ($campaignBanners as $campaignBanner) {
            if ($debugCampaignPublicId && $campaignBanner->campaign_id !== $debugCampaignId) {
                // when debugging specific campaign, do not render other campaigns
                continue;
            }

            $c = $campaigns[$campaignBanner->campaign_id];

            if ($debug) {
                $evaluationMessages[] = "Displaying campaign [{$c->public_id}] (variant [$campaignBanner->public_id], banner [{$campaignBanner->banner->public_id}])";
            }
            $matchedSnippets = array_intersect_key(
                $this->snippets,
                array_flip($campaignBannerSnippets[$campaignBanner->public_id]),
            );

            $displayData[] = $showtimeResponse->renderCampaign(
                variant: $campaignBanner,
                campaign: $campaigns[$campaignBanner->campaign_id],
                alignments: $alignments,
                dimensions: $dimensions,
                positions: $positions,
                colorSchemes: $colorSchemes,
                snippets: $matchedSnippets,
                userData: $data,
            );
        }

        return $showtimeResponse->success(
            $callback,
            $displayData,
            array_values($activeCampaigns), // make sure $activeCampaigns is always encoded as array in JSON
            $segmentAggregator->getProviderData(),
            $suppressedBanners,
            $evaluationMessages
        );
    }

    public function prioritizeCampaignBannerOnPosition(array $campaigns, array $campaignBanners, array &$activeCampaigns, array &$suppressedBanners): array
    {
        $bannersOnPosition = [];
        foreach ($campaignBanners as $campaignBanner) {
            $position = "{$campaignBanner->banner->display_type}_{$campaignBanner->banner->position}_{$campaignBanner->banner->target_selector}";

            // add unique suffix to banner position, so it doesn't suppress other visible and hidden banners remp/remp#1346
            if ($campaignBanner->banner->template === 'html' && $campaignBanner->banner->htmlTemplate->dimensions === 'hidden') {
                $position .= "_hidden_{$campaignBanner->id}";
            }

            if (isset($bannersOnPosition[$position])) {
                /** @var CampaignBanner $bannerOnPosition */
                $bannerOnPosition = $bannersOnPosition[$position];
                /** @var Campaign $currentCampaign */
                $currentCampaign = $campaigns[$bannerOnPosition->campaign_id];
                /** @var Campaign $newCampaign */
                $newCampaign = $campaigns[$campaignBanner->campaign_id];

                if ($this->isNewCampaignPriorityHigher($newCampaign, $currentCampaign)) {
                    $suppressedBanners[] = [
                        'campaign_banner_public_id' => $bannerOnPosition->public_id,
                        'banner_public_id' => $bannerOnPosition->banner->public_id,
                        'campaign_public_id' => $currentCampaign->public_id,
                        'position' => $position,
                    ];
                    $bannersOnPosition[$position] = $campaignBanner;
                    $this->removeCampaignByUuid($activeCampaigns, $currentCampaign->uuid);
                } else {
                    $suppressedBanners[] = [
                        'campaign_banner_public_id' => $campaignBanner->public_id,
                        'banner_public_id' => $campaignBanner->banner->public_id,
                        'campaign_public_id' => $newCampaign->public_id,
                        'position' => $position,
                    ];
                    $this->removeCampaignByUuid($activeCampaigns, $newCampaign->uuid);
                }
            } else {
                $bannersOnPosition[$position] = $campaignBanner;
            }
        }

        // set winning campaigns
        foreach ($suppressedBanners as $i => $suppressedBanner) {
            $winningBanner = $bannersOnPosition[$suppressedBanner['position']];
            $winningCampaign = $campaigns[$winningBanner->campaign_id];
            $suppressedBanners[$i]['suppressed_by_campaign_public_id'] = $winningCampaign->public_id;
            unset($suppressedBanners[$i]['position']);
        }

        return array_values($bannersOnPosition);
    }

    private function isNewCampaignPriorityHigher(Campaign $newCampaign, Campaign $currentCampaign): bool
    {
        // campaign with more banners has higher priority
        if ($newCampaign->campaignBanners->count() > $currentCampaign->campaignBanners->count()) {
            return true;
        }

        if ($currentCampaign->campaignBanners->count() === $newCampaign->campaignBanners->count()) {
            // campaign with more recent updates has higher priority
            if ($newCampaign->updated_at > $currentCampaign->updated_at) {
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

    /**
     * Determines what campaign banner should be displayed for user/browser
     * Returns either campaign banner or string message saying why no campaign banner will be display.
     *
     * @param Campaign    $campaign
     * @param             $userData
     * @param array       $activeCampaigns
     *
     * @return CampaignBanner|string
     */
    protected function campaignBannerToDisplay(
        Campaign $campaign,
        $userData,
        array &$activeCampaigns,
    ): CampaignBanner|string {
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
            return "Campaign is not running";
        }

        /** @var Collection $campaignBanners */
        $campaignBanners = $campaign->campaignBanners->keyBy('uuid');

        // banner
        if ($campaignBanners->count() == 0) {
            $this->logger->error("Active campaign [{$campaign->uuid}] has no banner set");
            return "Campaign not shown because it has no banner set";
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

        // unset seen variant if it was deleted
        /** @var CampaignBanner $seenVariant */
        $seenVariant = $campaignBanners->get($variantUuid);
        if (!$seenVariant) {
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
            return "Campaign not shown because there is no CampaignBanner with UUID [{$variantUuid}]";
        }

        // check if campaign is set to be seen only once per session
        $campaignsSeenInSession = $userData->campaignsSession ?? [];
        if ($campaign->once_per_session && $campaignsSeenInSession) {
            $seen = isset($campaignsSeenInSession->{$campaign->uuid});
            if ($seen) {
                return "Campaign not shown because it can be displayed only once per session";
            }
        }

        // signed in state
        if (isset($campaign->signed_in) && $campaign->signed_in !== (bool) $userId) {
            $requireSignedUser = (bool)$campaign->signed_in;
            return "Campaign not shown because it requires " . ($requireSignedUser ? "signed user" : "anonymous user");
        }

        // using adblock?
        if ($campaign->using_adblock !== null) {
            if (!isset($userData->usingAdblock)) {
                $this->logger->error("Unable to load if user with ID [{$userId}] & browserId [{$browserId}] is using AdBlock");
                return "Campaign not shown because system was unable to check if user with ID [{$userId}] & browserId [{$browserId}] is using AdBlock";
            }
            if (($campaign->using_adblock && !$userData->usingAdblock)) {
                return "Campaign not shown because user with ID [{$userId}] is not using AdBlock";
            }
            if ($campaign->using_adblock === false && $userData->usingAdblock) {
                return "Campaign not shown because user with ID [{$userId}] is using AdBlock";
            }
        }

        // url filters
        if ($campaign->url_filter === Campaign::URL_FILTER_EXCEPT_AT) {
            foreach ($campaign->url_patterns as $urlPattern) {
                if (strpos($userData->url, $urlPattern) !== false) {
                    return "Campaign not shown because of the URL filter [{$urlPattern}] (matched URL [{$userData->url}])";
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
                return "Campaign not shown because it does not match any of given URL filter(s)";
            }
        }

        // source referer filters
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_REFERER_EXCEPT_AT && $userData->referer) {
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->referer, $sourcePattern) !== false) {
                    return "Campaign not shown because of the referer source filter [{$sourcePattern}] (provided referer [{$userData->referer}])";
                }
            }
        }
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_REFERER_ONLY_AT) {
            if (!isset($userData->referer) || $userData->referer === '') {
                return "Campaign not shown because user didn't provide a referer and campaign has filter on it";
            }
            $matched = false;
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->referer, $sourcePattern) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return "Campaign not shown because it does not match referer source filter (provided referer [{$userData->referer}])";
            }
        }

        // session referer filters
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_SESSION_EXCEPT_AT && $userData->sessionReferer) {
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->sessionReferer, $sourcePattern) !== false) {
                    return "Campaign not shown because of the session source filter [{$sourcePattern}] (provided session source [{$userData->sessionReferer}])";
                }
            }
        }
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_SESSION_ONLY_AT) {
            if (!isset($userData->sessionReferer) || $userData->sessionReferer === '') {
                return "Campaign not shown because user didn't provide a session source and campaign has filter on it";
            }
            $matched = false;
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->sessionReferer, $sourcePattern) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return "Campaign not shown because it does not match session source filter (provided session referer [{$userData->sessionReferer}])";
            }
        }

        // user agent specific rules
        if (isset($userData->userAgent)) {
            // device rules
            if ($this->deviceRulesEvaluator->isAcceptedByDeviceRules($userData->userAgent, $campaign) === false) {
                return "Campaign not shown because it's not accepted according to device rules";
            }

            // operating system rules
            if ($this->deviceRulesEvaluator->isAcceptedByOperatingSystemRules($userData->userAgent, $campaign) === false) {
                return "Campaign not shown because it's not accepted according to operating system rules";
            }
        } else {
            $this->logger->error("Unable to load user agent for userId [{$userId}]");
        }

        // country rules
        if (!$campaign->countries->isEmpty()) {
            // load country ISO code based on IP
            try {
                $countryCode = $this->geoReader->countryCode($this->getRequest()->ip());
                if ($countryCode === null) {
                    $this->logger->debug("Unable to identify country for campaign '{$campaign->id}'.");
                    return "Campaign not shown because we were unable to identify country";
                }
            } catch (\MaxMind\Db\Reader\InvalidDatabaseException $e) {
                $this->logger->error("Unable to identify country for campaign '{$campaign->id}': " . $e->getMessage());
                return "Campaign not shown because we were unable to identify country (InvalidDatabaseException)";
            } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
                // This may happen, do not throw exception here
                // see https://github.com/maxmind/GeoIP2-php/issues/105
                return "Campaign not shown because we were unable to identify location from IP address [{$this->getRequest()->ip()}]";
            }

            // check against white / black listed countries

            if (!$campaign->countriesBlacklist->isEmpty() && $campaign->countriesBlacklist->contains('iso_code', $countryCode)) {
                return "Campaign not shown because country [{$countryCode}] is blacklisted";
            }
            if (!$campaign->countriesWhitelist->isEmpty() && !$campaign->countriesWhitelist->contains('iso_code', $countryCode)) {
                return "Campaign not shown because country [{$countryCode}] is not whitelisted";
            }
        }

        // languages rules
        if (!empty($userData->language)) {
            $userData->primaryLanguage = \Locale::getPrimaryLanguage($userData->language);
        }
        if (!empty($campaign->languages)) {
            if (empty($userData->primaryLanguage) || !in_array($userData->primaryLanguage, $campaign->languages, true)) {
                return "Campaign not shown because of the language rules";
            }
        }

        // segment
        $segmentRulesOk = $this->evaluateSegmentRules($campaign, $browserId, $userId);
        if (!$segmentRulesOk) {
            return "Campaign not shown because it does not pass the segment rules (userId: [$userId], browserId: [$browserId])";
        }

        // pageview attributes - check if sent pageview attributes match conditions
        if (!empty($campaign->pageview_attributes)) {
            if (empty($userData->pageviewAttributes)) {
                return "Campaign not shown because user sent no pageview attributes";
            }

            foreach ($campaign->pageview_attributes as $attribute) {
                $attrName = $attribute['name'];
                $attrValue = $attribute['value'];
                $attrOperator = $attribute['operator'];

                if ($attrOperator === self::PAGEVIEW_ATTRIBUTE_OPERATOR_IS) {
                    if (!property_exists($userData->pageviewAttributes, $attrName)) {
                        return "Campaign not shown because user has no [$attrName] attribute";
                    }
                    if (is_array($userData->pageviewAttributes->{$attrName})) {
                        if (!in_array($attrValue, $userData->pageviewAttributes->{$attrName})) {
                            return "Campaign not shown because user attribute [$attrName] (array) does not contain '$attrValue'";
                        }
                    } elseif ($userData->pageviewAttributes->{$attrName} !== $attrValue) {
                        return "Campaign not shown because user attribute [$attrName] value is not '$attrValue'";
                    }
                }

                if ($attrOperator === self::PAGEVIEW_ATTRIBUTE_OPERATOR_IS_NOT) {
                    if (property_exists($userData->pageviewAttributes, $attrName)) {
                        if (is_array($userData->pageviewAttributes->{$attrName})) {
                            if (in_array($attrValue, $userData->pageviewAttributes->{$attrName})) {
                                return "Campaign not shown because user attribute [$attrName] (array) contains '$attrValue'";
                            }
                        } else {
                            if ($userData->pageviewAttributes->{$attrName} === $attrValue) {
                                return "Campaign not shown because user attribute [$attrName] equals '$attrValue'";
                            }
                        }
                    }
                }
            }
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
                return "Campaign not shown because user has no pageview count (probably old version of remplib)";
            }

            $displayBanner = $campaign->pageview_rules['display_banner'] ?? null;
            $displayBannerEvery = $campaign->pageview_rules['display_banner_every'] ?? 1;
            if ($displayBanner === 'every' && $pageviewCount % $displayBannerEvery !== 0) {
                return "Campaign not shown because of 'display-banner-every' pageview rule (pageview count [$pageviewCount])";
            }

            $sessionCampaign = $campaignsSeenInSession->{$campaign->uuid} ?? $campaignsSeenInSession->{$campaign->public_id} ?? null;

            if (property_exists($seenCampaign, 'closedAt')) {
                $afterClosedRule = $campaign->pageview_rules['after_banner_closed_display'] ?? null;
                $afterClosedHours = $campaign->pageview_rules['after_closed_hours'] ?? 0;
                if ($afterClosedRule === 'never') {
                    return "Campaign not shown because of 'after-close-rule=never' pageview rule";
                }

                if ($afterClosedRule === 'never_in_session' && isset($sessionCampaign->closedAt)) {
                    return "Campaign not shown because of 'after-close-rule=never_in_session' pageview rule";
                }

                if ($afterClosedRule === 'close_for_hours' && $afterClosedHours) {
                    $threshold = time() - $afterClosedHours * 60 * 60;
                    if ($seenCampaign->closedAt > $threshold) {
                        return "Campaign not shown because of 'after-close-rule=close_for_hours' pageview rule";
                    }
                }
            }

            if (property_exists($seenCampaign, 'clickedAt')) {
                $afterClickedRule = $campaign->pageview_rules['after_banner_clicked_display'] ?? null;
                $afterClickedHours = $campaign->pageview_rules['after_clicked_hours'] ?? 0;
                if ($afterClickedRule === 'never') {
                    return "Campaign not shown because of 'after-click-rule=never' pageview rule";
                }
                if ($afterClickedRule === 'never_in_session' && isset($sessionCampaign->clickedAt)) {
                    return "Campaign not shown because of 'after-click-rule=never_in_session' pageview rule";
                }

                if ($afterClickedRule === 'close_for_hours' && $afterClickedHours) {
                    $threshold = time() - $afterClickedHours * 60 * 60;
                    if ($seenCampaign->clickedAt > $threshold) {
                        return "Campaign not shown because of 'after-click-rule=close_for_hours' pageview rule";
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
                return "Campaign not shown because of missing seenCount value (probably old version of remplib)";
            }

            $displayTimes = $campaign->pageview_rules['display_times'] ?? null;
            if ($displayTimes && $seenCount >= $campaign->pageview_rules['display_n_times']) {
                return "Campaign not shown because it was already seen more than [{$campaign->pageview_rules['display_n_times']} times (specifically [$displayTimes] times)]";
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
            $keys[] = \Remp\CampaignModule\Models\Position\Map::POSITIONS_MAP_REDIS_KEY;
        }

        if (!$this->dimensionMap) {
            $keys[] = \Remp\CampaignModule\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY;
        }

        if (!$this->alignmentsMap) {
            $keys[] = \Remp\CampaignModule\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY;
        }

        if (!$this->colorSchemesMap) {
            $keys[] = \Remp\CampaignModule\Models\ColorScheme\Map::COLOR_SCHEMES_MAP_REDIS_KEY;
        }

        if (!$this->snippets) {
            $keys[] = \Remp\CampaignModule\Snippet::REDIS_CACHE_KEY;
        }

        $maps = $this->redis->mget($keys);
        reset($keys);
        foreach ($maps as $map) {
            $key = current($keys);

            switch ($key) {
                case \Remp\CampaignModule\Models\Position\Map::POSITIONS_MAP_REDIS_KEY:
                    $this->positionMap = json_decode($map, true) ?? [];
                    break;
                case \Remp\CampaignModule\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY:
                    $this->dimensionMap = json_decode($map, true) ?? [];
                    break;
                case \Remp\CampaignModule\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY:
                    $this->alignmentsMap = json_decode($map, true) ?? [];
                    break;
                case \Remp\CampaignModule\Models\ColorScheme\Map::COLOR_SCHEMES_MAP_REDIS_KEY:
                    $this->colorSchemesMap = json_decode($map, true) ?? [];
                    break;
                case \Remp\CampaignModule\Snippet::REDIS_CACHE_KEY:
                    $snippets = json_decode($map, true) ?? [];
                    foreach ($snippets as $code => $snippet) {
                        $this->snippets[$code] = $snippet;
                    }
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

                $belongsToSegment = $this->checkUserInSegment($campaignSegment, $userId);
            } else {
                $belongsToSegment = $this->checkBrowserInSegment($campaignSegment, $browserId);
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

        $cacheKey = SegmentAggregator::cacheKey($campaignSegment). '|timestamp';
        if (isset($this->localCache[$cacheKey])) {
            return true;
        }

        $cacheKeyTimeStamp = $this->redis->get($cacheKey);
        if ($cacheKeyTimeStamp) {
            $this->localCache[$cacheKey] = true;
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

    private function checkUserInSegment(CampaignSegment $campaignSegment, $userId): bool
    {
        $cacheKey = SegmentAggregator::cacheKey($campaignSegment). '|user|'.$userId;
        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        $belongsToSegment = $this->segmentAggregator->checkUser($campaignSegment, (string) $userId);
        $this->localCache[$cacheKey] = $belongsToSegment;

        return $belongsToSegment;
    }

    private function checkBrowserInSegment(CampaignSegment $campaignSegment, $browserId): bool
    {
        $cacheKey = SegmentAggregator::cacheKey($campaignSegment). '|browser|'.$browserId;
        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        $belongsToSegment = $this->segmentAggregator->checkBrowser($campaignSegment, (string)$browserId);
        $this->localCache[$cacheKey] = $belongsToSegment;

        return $belongsToSegment;
    }
}
