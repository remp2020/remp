<?php

namespace Remp\CampaignModule\Http\Showtime;

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Models\Alignment\Map as AlignmentMap;
use Remp\CampaignModule\Models\ColorScheme\Map as ColorSchemeMap;
use Remp\CampaignModule\Models\Dimension\Map as DimensionMap;
use Remp\CampaignModule\Models\Position\Map as PositionMap;
use Remp\CampaignModule\Models\Targeting\TargetingMatchEnum;
use Remp\CampaignModule\Snippet;
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
    private array $localCache = [];
    private ShowtimeTimer $timer;

    public function __construct(
        private ClientInterface|\Redis $redis,
        private SegmentAggregator $segmentAggregator,
        private LazyGeoReader $geoReader,
        private ShowtimeConfig $showtimeConfig,
        private DeviceRulesEvaluator $deviceRulesEvaluator,
        private LoggerInterface $logger,
        ?ShowtimeTimer $timer = null,
    ) {
        $this->timer = $timer ?? new ShowtimeTimer(microtime(true));
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

    public function getShowtimeConfig(): ShowtimeConfig
    {
        return $this->showtimeConfig;
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
        if (isset($data->debug->key)) {
            // empty key would let anyone debug if CAMPAIGN_DEBUG_KEY is configured but empty
            $debugKey = $this->showtimeConfig->getDebugKey();
            if ($debugKey !== null && $debugKey !== '' && $data->debug->key === $debugKey) {
                $this->timer->enableReport();
                $debugCampaignPublicId = $data->debug->campaignPublicId ?? null;

                if (isset($data->debug->userId)) {
                    $data->userId = $data->debug->userId;
                    $userId = $data->debug->userId;
                }

                if (isset($data->debug->referer)) {
                    $data->referer = $data->debug->referer;
                }
                if (isset($data->debug->sessionReferer)) {
                    $data->sessionReferer = $data->debug->sessionReferer;
                }
            } else {
                $evaluationMessages[] = 'Invalid debug key';
            }
        }

        $oneTimeBannerKeys = [];
        if ($this->showtimeConfig->isOneTimeBannerEnabled()) {
            // user's one-time banners have precedence over browser's one-time banners
            if ($userId) {
                $oneTimeBannerKeys[] = self::BANNER_ONETIME_USER_KEY . ":$userId";
            }
            $oneTimeBannerKeys[] = self::BANNER_ONETIME_BROWSER_KEY . ":$browserId";
        }

        // fetch everything needed upfront in a single round trip to Redis
        $this->timer->start('fetch');
        $results = $this->pipeline(function ($pipe) use ($oneTimeBannerKeys) {
            $pipe->get(Campaign::ACTIVE_CAMPAIGN_IDS);
            $pipe->mget([
                PositionMap::POSITIONS_MAP_REDIS_KEY,
                DimensionMap::DIMENSIONS_MAP_REDIS_KEY,
                AlignmentMap::ALIGNMENTS_MAP_REDIS_KEY,
                ColorSchemeMap::COLOR_SCHEMES_MAP_REDIS_KEY,
                Snippet::REDIS_CACHE_KEY,
            ]);
            foreach ($oneTimeBannerKeys as $key) {
                $pipe->exists($key);
            }
        });

        $this->timer->stop('fetch');

        [$campaignIdsJson, $mapsJson] = $results;
        $oneTimeBannerKeysExist = array_slice($results, 2); // EXISTS result of each $oneTimeBannerKeys item

        [$positionsJson, $dimensionsJson, $alignmentsJson, $colorSchemesJson, $snippetsJson] = $mapsJson;
        $positions = json_decode($positionsJson ?? '[]', true);
        $dimensions = json_decode($dimensionsJson ?? '[]', true);
        $alignments = json_decode($alignmentsJson ?? '[]', true);
        $colorSchemes = json_decode($colorSchemesJson ?? '[]', true);
        $snippets = json_decode($snippetsJson ?? '[]', true);

        $displayData = [];

        // Try to load one-time banners (having precedence over campaigns)
        foreach ($oneTimeBannerKeys as $i => $key) {
            if (!$oneTimeBannerKeysExist[$i]) {
                continue;
            }
            $banner = $this->loadOneTimeBanner($key);
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
                    suppressedBanners: [],
                    evaluationMessages: $evaluationMessages,
                );
            }
        }

        $campaignIds = json_decode($campaignIdsJson ?? '[]');
        if (count($campaignIds) === 0) {
            return $showtimeResponse->success(
                callback: $callback,
                data: [],
                activeCampaigns: [],
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

        // fetch all running campaigns and their possibly active snippets at once
        $this->timer->start('fetch');
        [$fetchedCampaigns, $fetchedCampaignSnippets] = $this->pipeline(
            function ($pipe) use ($cachedCampaignKeys, $cachedCampaignSnippetCodesKeys) {
                $pipe->mget($cachedCampaignKeys);
                $pipe->mget($cachedCampaignSnippetCodesKeys);
            }
        );
        $this->timer->stop('fetch');
        $this->timer->increment('campaigns', count($fetchedCampaigns));

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

        $this->timer->start('hydrate');
        foreach ($fetchedCampaigns as $fetchedCampaign) {
            /** @var Campaign $campaign */
            $campaign = new Campaign();
            $campaign->hydrateFromCache(json_decode($fetchedCampaign, true));
            $campaigns[$campaign->id] = $campaign;
            if ($campaign->public_id === $debugCampaignPublicId) {
                $debugCampaignId = $campaign->id;
            }
        }
        $this->timer->stop('hydrate');

        if ($userId) {
            $this->timer->start('segments_prefetch');
            $this->prefetchUserSegments($campaigns, (string) $userId);
            $this->timer->stop('segments_prefetch');
        }

        foreach ($campaigns as $campaign) {
            // includes the 'devices' and 'segments' phases
            $this->timer->start('evaluate');
            $result = $this->campaignBannerToDisplay(
                campaign: $campaign,
                userData: $data,
                activeCampaigns: $activeCampaigns,
            );
            $this->timer->stop('evaluate');
            if ($result instanceof CampaignBanner) {
                $campaignBanners[] = $result;
            } else if ($debugCampaignPublicId && $campaign->public_id === $debugCampaignPublicId) {
                // only add evaluation message for debugged campaign
                $evaluationMessages[] = $result;
            }
        }

        $this->timer->start('banners');
        $this->loadBannerRelations($campaignBanners);
        $this->timer->stop('banners');
        foreach ($campaignBanners as $campaignBanner) {
            $activeCampaigns[$campaignBanner->campaign_id]->banner_public_id = $campaignBanner->banner?->public_id;
        }

        if ($this->showtimeConfig->isPrioritizeBannerOnSamePosition()) {
            $campaignBanners = $this->prioritizeCampaignBannerOnPosition($campaigns, $campaignBanners, $activeCampaigns, $suppressedBanners);
        }

        // If $debugCampaignPublicId is set, filter out everything else (after prioritization has been done)
        if ($debugCampaignPublicId) {
            $activeCampaigns = array_filter($activeCampaigns, static fn($c) => $debugCampaignPublicId === $c->public_id);
        }

        $this->timer->start('render');
        foreach ($campaignBanners as $campaignBanner) {
            if ($debugCampaignPublicId && $campaignBanner->campaign_id !== $debugCampaignId) {
                // when debugging specific campaign, do not render other campaigns
                continue;
            }
            $matchedSnippets = array_intersect_key(
                $snippets,
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
        $this->timer->stop('render');

        return $showtimeResponse->success(
            $callback,
            $displayData,
            array_values($activeCampaigns), // make sure $activeCampaigns is always encoded as array in JSON, remove keys
            $suppressedBanners,
            $evaluationMessages
        );
    }

    /**
     * @param CampaignBanner[] $campaignBanners
     */
    private function loadBannerRelations(array $campaignBanners): void
    {
        $bannerIds = [];
        foreach ($campaignBanners as $campaignBanner) {
            if ($campaignBanner->banner_id !== null) {
                $bannerIds[] = $campaignBanner->banner_id;
            }
        }
        $banners = Banner::loadCachedBanners($this->redis, $bannerIds);

        foreach ($campaignBanners as $campaignBanner) {
            // control group variants have no banner
            if ($campaignBanner->banner_id === null) {
                $campaignBanner->setRelation('banner', null);
                continue;
            }

            if (!isset($banners[$campaignBanner->banner_id])) {
                throw new \Exception("Banner with ID {$campaignBanner->banner_id} is not present in cache");
            }
            $campaignBanner->setRelation('banner', $banners[$campaignBanner->banner_id]);
        }
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
                    unset($activeCampaigns[$currentCampaign->id]);
                } else {
                    $suppressedBanners[] = [
                        'campaign_banner_public_id' => $campaignBanner->public_id,
                        'banner_public_id' => $campaignBanner->banner->public_id,
                        'campaign_public_id' => $newCampaign->public_id,
                        'position' => $position,
                    ];
                    unset($activeCampaigns[$newCampaign->id]);
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

        $variantUuid = null;

        // find variant previously displayed to user
        $seenCampaigns = $userData->campaigns ?? false;
        if ($seenCampaigns) {
            if (isset($seenCampaigns->{$campaign->uuid})) {
                $variantUuid = $seenCampaigns->{$campaign->uuid}->variantId ?? null;
            }

            if (isset($seenCampaigns->{$campaign->public_id}->variantId)) {
                foreach ($campaign->campaignBanners as $campaignBanner) {
                    if ($campaignBanner->public_id === $seenCampaigns->{$campaign->public_id}->variantId) {
                        $variantUuid = $campaignBanner->uuid ?? null;
                        break;
                    }
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
            return "Campaign not executed because there is no CampaignBanner with UUID [{$variantUuid}]";
        }

        // check if campaign is set to be seen only once per session
        $campaignsSeenInSession = $userData->campaignsSession ?? [];
        if ($campaign->once_per_session && $campaignsSeenInSession) {
            $seen = isset($campaignsSeenInSession->{$campaign->uuid});
            if ($seen) {
                return "Campaign not executed because it can be displayed only once per session";
            }
        }

        // signed in state
        if (isset($campaign->signed_in) && $campaign->signed_in !== (bool) $userId) {
            $requireSignedUser = (bool)$campaign->signed_in;
            return "Campaign not executed because it requires " . ($requireSignedUser ? "signed user" : "anonymous user");
        }

        // using adblock?
        if ($campaign->using_adblock !== null) {
            if (!isset($userData->usingAdblock)) {
                $this->logger->error("Unable to load if user with ID [{$userId}] & browserId [{$browserId}] is using AdBlock");
                return "Campaign not executed because system was unable to check if user with ID [{$userId}] & browserId [{$browserId}] is using AdBlock";
            }
            if (($campaign->using_adblock && !$userData->usingAdblock)) {
                return "Campaign not executed because user with ID [{$userId}] is not using AdBlock";
            }
            if ($campaign->using_adblock === false && $userData->usingAdblock) {
                return "Campaign not executed because user with ID [{$userId}] is using AdBlock";
            }
        }

        // url filters
        if ($campaign->url_filter === Campaign::URL_FILTER_EXCEPT_AT) {
            foreach ($campaign->url_patterns as $urlPattern) {
                if (strpos($userData->url, $urlPattern) !== false) {
                    return "Campaign not executed because of the URL filter [{$urlPattern}] (matched URL [{$userData->url}])";
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
                return "Campaign not executed because it does not match any of given URL filter(s)";
            }
        }

        // source referer filters
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_REFERER_EXCEPT_AT && $userData->referer) {
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->referer, $sourcePattern) !== false) {
                    return "Campaign not executed because of the referer source filter [{$sourcePattern}] (provided referer [{$userData->referer}])";
                }
            }
        }
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_REFERER_ONLY_AT) {
            if (!isset($userData->referer) || $userData->referer === '') {
                return "Campaign not executed because user didn't provide a referer and campaign has filter on it";
            }
            $matched = false;
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->referer, $sourcePattern) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return "Campaign not executed because it does not match referer source filter (provided referer [{$userData->referer}])";
            }
        }

        // session referer filters
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_SESSION_EXCEPT_AT && $userData->sessionReferer) {
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->sessionReferer, $sourcePattern) !== false) {
                    return "Campaign not executed because of the session source filter [{$sourcePattern}] (provided session source [{$userData->sessionReferer}])";
                }
            }
        }
        if ($campaign->source_filter === Campaign::SOURCE_FILTER_SESSION_ONLY_AT) {
            if (!isset($userData->sessionReferer) || $userData->sessionReferer === '') {
                return "Campaign not executed because user didn't provide a session source and campaign has filter on it";
            }
            $matched = false;
            foreach ($campaign->source_patterns as $sourcePattern) {
                if (strpos($userData->sessionReferer, $sourcePattern) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return "Campaign not executed because it does not match session source filter (provided session referer [{$userData->sessionReferer}])";
            }
        }

        // user agent specific rules
        if (isset($userData->userAgent)) {
            // device rules
            $this->timer->start('devices');
            $acceptedByDevice = $this->deviceRulesEvaluator->isAcceptedByDeviceRules($userData->userAgent, $campaign);
            $this->timer->stop('devices');
            if ($acceptedByDevice === false) {
                return "Campaign not executed because it's not accepted according to device rules";
            }

            // operating system rules
            $this->timer->start('devices');
            $acceptedByOperatingSystem = $this->deviceRulesEvaluator->isAcceptedByOperatingSystemRules($userData->userAgent, $campaign);
            $this->timer->stop('devices');
            if ($acceptedByOperatingSystem === false) {
                return "Campaign not executed because it's not accepted according to operating system rules";
            }
        } else {
            $this->logger->error("Unable to load user agent for userId [{$userId}]");
        }

        // IP range rules
        if (!$campaign->ipRanges->isEmpty()) {
            $visitorIp = $this->getRequest()->ip();
            $ipRangesMatch = $campaign->matchIpRanges($visitorIp);

            if ($ipRangesMatch === TargetingMatchEnum::Blacklisted) {
                return "Campaign not shown because IP [{$visitorIp}] is blacklisted";
            }
            if ($ipRangesMatch === TargetingMatchEnum::NotWhitelisted) {
                return "Campaign not shown because IP [{$visitorIp}] is not whitelisted";
            }
        }

        // country rules
        if (!$campaign->countries->isEmpty()) {
            // load country ISO code based on IP
            try {
                $countryCode = $this->geoReader->countryCode($this->getRequest()->ip());
                if ($countryCode === null) {
                    $this->logger->debug("Unable to identify country for campaign '{$campaign->id}'.");
                    return "Campaign not executed because we were unable to identify country";
                }
            } catch (\MaxMind\Db\Reader\InvalidDatabaseException $e) {
                $this->logger->error("Unable to identify country for campaign '{$campaign->id}': " . $e->getMessage());
                return "Campaign not executed because we were unable to identify country (InvalidDatabaseException)";
            } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
                // This may happen, do not throw exception here
                // see https://github.com/maxmind/GeoIP2-php/issues/105
                return "Campaign not executed because we were unable to identify location from IP address [{$this->getRequest()->ip()}]";
            }

            // check against white / black listed countries
            $countryMatch = $campaign->matchCountry($countryCode);

            if ($countryMatch === TargetingMatchEnum::Blacklisted) {
                return "Campaign not executed because country [{$countryCode}] is blacklisted";
            }
            if ($countryMatch === TargetingMatchEnum::NotWhitelisted) {
                return "Campaign not executed because country [{$countryCode}] is not whitelisted";
            }
        }

        // languages rules
        if (!empty($userData->language)) {
            $userData->primaryLanguage = \Locale::getPrimaryLanguage($userData->language);
        }
        if (!empty($campaign->languages)) {
            if (empty($userData->primaryLanguage) || !in_array($userData->primaryLanguage, $campaign->languages, true)) {
                return "Campaign not executed because of the language rules";
            }
        }

        // pageview attributes - check if sent pageview attributes match conditions
        if (!empty($campaign->pageview_attributes)) {
            if (empty($userData->pageviewAttributes)) {
                return "Campaign not executed because user sent no pageview attributes";
            }

            foreach ($campaign->pageview_attributes as $attribute) {
                $attrName = $attribute['name'];
                $attrValue = $attribute['value'];
                $attrOperator = $attribute['operator'];

                if ($attrOperator === self::PAGEVIEW_ATTRIBUTE_OPERATOR_IS) {
                    if (!property_exists($userData->pageviewAttributes, $attrName)) {
                        return "Campaign not executed because user has no [$attrName] attribute";
                    }
                    if (is_array($userData->pageviewAttributes->{$attrName})) {
                        if (!in_array($attrValue, $userData->pageviewAttributes->{$attrName})) {
                            return "Campaign not executed because user attribute [$attrName] (array) does not contain '$attrValue'";
                        }
                    } elseif ($userData->pageviewAttributes->{$attrName} !== $attrValue) {
                        return "Campaign not executed because user attribute [$attrName] value is not '$attrValue'";
                    }
                }

                if ($attrOperator === self::PAGEVIEW_ATTRIBUTE_OPERATOR_IS_NOT) {
                    if (property_exists($userData->pageviewAttributes, $attrName)) {
                        if (is_array($userData->pageviewAttributes->{$attrName})) {
                            if (in_array($attrValue, $userData->pageviewAttributes->{$attrName})) {
                                return "Campaign not executed because user attribute [$attrName] (array) contains '$attrValue'";
                            }
                        } else {
                            if ($userData->pageviewAttributes->{$attrName} === $attrValue) {
                                return "Campaign not executed because user attribute [$attrName] equals '$attrValue'";
                            }
                        }
                    }
                }
            }
        }

        // segment rules are evaluated as the last targeting rule, checking them might require HTTP requests
        $this->timer->start('segments');
        $segmentRulesOk = $this->evaluateSegmentRules($campaign, $browserId, $userId);
        $this->timer->stop('segments');
        if (!$segmentRulesOk) {
            return "Campaign not executed because it does not pass the segment rules (userId: [$userId], browserId: [$browserId])";
        }

        // Active campaign is campaign that targets selected user (previous rules were passed),
        // but whether it displays or not depends on pageview counting rules (every n-th page, up to N pageviews).
        // We need to track such campaigns on the client-side too.
        $activeCampaigns[$campaign->id] = (object) [
            'uuid' => $campaign->uuid,
            'public_id' => $campaign->public_id,
        ];

        $seenCampaign = $seenCampaigns->{$campaign->uuid} ?? $seenCampaigns->{$campaign->public_id} ?? null;

        // pageview rules - check display banner every n-th request
        if ($campaign->pageview_rules !== null) {
            $pageviewCount = $seenCampaign === null ? 0 : ($seenCampaign->count ?? null);

            if ($pageviewCount === null) {
                // if campaign is recorder as seen but has no pageview count,
                // it means there is a probably old version or remplib cached on the client
                // do not show campaign (browser should reload the library)
                return "Campaign not executed because user has no pageview count (probably old version of remplib)";
            }

            if (($campaign->pageview_rules['display_banner'] ?? null) === 'every') {
                $displayBannerEvery = $campaign->pageview_rules['display_banner_every'] ?? 1;
                $firstDisplayCount = ($campaign->pageview_rules['display_banner_from'] ?? 1) - 1;

                if ($pageviewCount < $firstDisplayCount) {
                    return "Campaign not executed because of 'display-banner-from' pageview rule (pageview count [$pageviewCount])";
                }
                if (($pageviewCount - $firstDisplayCount) % $displayBannerEvery !== 0) {
                    return "Campaign not executed because of 'display-banner-every' pageview rule (pageview count [$pageviewCount])";
                }
            }
        }

        // pageview rules - check rules after banner was closed or clicked
        if ($seenCampaign !== null && $campaign->pageview_rules !== null) {
            $sessionCampaign = $campaignsSeenInSession->{$campaign->uuid} ?? $campaignsSeenInSession->{$campaign->public_id} ?? null;

            if (property_exists($seenCampaign, 'closedAt')) {
                $afterClosedRule = $campaign->pageview_rules['after_banner_closed_display'] ?? null;
                $afterClosedHours = $campaign->pageview_rules['after_closed_hours'] ?? 0;
                if ($afterClosedRule === 'never') {
                    return "Campaign not executed because of 'after-close-rule=never' pageview rule";
                }

                if ($afterClosedRule === 'never_in_session' && isset($sessionCampaign->closedAt)) {
                    return "Campaign not executed because of 'after-close-rule=never_in_session' pageview rule";
                }

                if ($afterClosedRule === 'close_for_hours' && $afterClosedHours) {
                    $threshold = time() - $afterClosedHours * 60 * 60;
                    if ($seenCampaign->closedAt > $threshold) {
                        return "Campaign not executed because of 'after-close-rule=close_for_hours' pageview rule";
                    }
                }
            }

            if (property_exists($seenCampaign, 'clickedAt')) {
                $afterClickedRule = $campaign->pageview_rules['after_banner_clicked_display'] ?? null;
                $afterClickedHours = $campaign->pageview_rules['after_clicked_hours'] ?? 0;
                if ($afterClickedRule === 'never') {
                    return "Campaign not executed because of 'after-click-rule=never' pageview rule";
                }
                if ($afterClickedRule === 'never_in_session' && isset($sessionCampaign->clickedAt)) {
                    return "Campaign not executed because of 'after-click-rule=never_in_session' pageview rule";
                }

                if ($afterClickedRule === 'close_for_hours' && $afterClickedHours) {
                    $threshold = time() - $afterClickedHours * 60 * 60;
                    if ($seenCampaign->clickedAt > $threshold) {
                        return "Campaign not executed because of 'after-click-rule=close_for_hours' pageview rule";
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
                return "Campaign not executed because of missing seenCount value (probably old version of remplib)";
            }

            $displayTimes = $campaign->pageview_rules['display_times'] ?? null;
            if ($displayTimes && $seenCount >= $campaign->pageview_rules['display_n_times']) {
                return "Campaign not executed because it was already seen more than [{$campaign->pageview_rules['display_n_times']} times (specifically [$displayTimes] times)]";
            }
        }

        return $variant;
    }

    public function displayForUser(Banner $banner, string $userId, int $expiresInSeconds)
    {
        $this->addOneTimeBanner(self::BANNER_ONETIME_USER_KEY . ":$userId", $banner, $expiresInSeconds);
    }

    public function displayForBrowser(Banner $banner, string $browserId, int $expiresInSeconds)
    {
        $this->addOneTimeBanner(self::BANNER_ONETIME_BROWSER_KEY . ":$browserId", $banner, $expiresInSeconds);
    }

    private function addOneTimeBanner(string $key, Banner $banner, int $expiresInSeconds): void
    {
        $now = Carbon::now();

        // remove already expired banners, so the set doesn't grow while its TTL keeps being extended
        foreach ($this->redis->zrange($key, 0, -1) as $member) {
            if ($this->isOneTimeBannerExpired($member, $now->getTimestamp())) {
                $this->redis->zrem($key, $member);
            }
        }

        $expiresAt = $now->getTimestamp() + $expiresInSeconds;
        $this->redis->zadd($key, $now->getTimestampMs(), "{$banner->id}:{$expiresAt}");

        // extend key expiration if necessary
        if ($this->redis->ttl($key) < $expiresInSeconds) {
            $this->redis->expire($key, $expiresInSeconds);
        }
    }

    private function evaluateSegmentRules(Campaign $campaign, $browserId, $userId = null)
    {
        if ($campaign->segments->isEmpty()) {
            return true;
        }

        $cachedSegments = [];
        $notCachedSegments = [];
        foreach ($campaign->segments as $campaignSegment) {
            if ($this->segmentAggregator->cacheEnabled($campaignSegment)) {
                $cachedSegments[] = $campaignSegment;
            } else {
                $notCachedSegments[] = $campaignSegment;
            }
        }

        // Check cached segments first so that we can halt early and avoid expensive non-cached segments check.
        foreach ([...$cachedSegments, ...$notCachedSegments] as $campaignSegment) {
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
            return $this->localCache[$cacheKey];
        }

        $this->timer->increment('segment_cache_checks');
        $this->localCache[$cacheKey] = (bool) $this->redis->get($cacheKey);
        return $this->localCache[$cacheKey];
    }

    /**
     * @param Campaign[] $campaigns
     */
    private function prefetchUserSegments(array $campaigns, string $userId): void
    {
        $campaignSegments = [];
        foreach ($campaigns as $campaign) {
            foreach ($campaign->segments as $campaignSegment) {
                $campaignSegments[SegmentAggregator::cacheKey($campaignSegment)] = $campaignSegment;
            }
        }

        $cacheTimestampKeys = [];
        foreach ($campaignSegments as $cacheKey => $campaignSegment) {
            if ($this->segmentAggregator->cacheEnabled($campaignSegment)) {
                $cacheTimestampKeys[$cacheKey] = $cacheKey . '|timestamp';
            }
        }

        if (count($cacheTimestampKeys) > 0) {
            $cacheTimestamps = $this->redis->mget(array_values($cacheTimestampKeys));
            $i = 0;
            foreach ($cacheTimestampKeys as $cacheKey => $cacheTimestampKey) {
                $isCacheValid = (bool) $cacheTimestamps[$i++];
                $this->localCache[$cacheTimestampKey] = $isCacheValid;
                if (!$isCacheValid) {
                    // segment is not cached yet, campaigns using it are rejected without checking the membership
                    unset($campaignSegments[$cacheKey]);
                }
            }
        }

        $belongsToSegments = $this->segmentAggregator->batchCheckUserInCache($campaignSegments, $userId);
        foreach ($belongsToSegments as $cacheKey => $belongsToSegment) {
            $this->localCache[$cacheKey . '|user|' . $userId] = $belongsToSegment;
        }

        $this->timer->increment('segments_prefetched', count($belongsToSegments));
    }

    private function pipeline(callable $commands): array
    {
        if ($this->redis instanceof \Redis) {
            $pipe = $this->redis->pipeline();
            $commands($pipe);
            return $pipe->exec();
        }

        return $this->redis->pipeline($commands);
    }

    private function loadOneTimeBanner(string $key): ?Banner
    {
        $now = Carbon::now()->getTimestamp();

        // pop the earliest banner; expired banners are popped (removed) too, but never displayed
        while ($popped = $this->redis->zpopmin($key)) {
            $member = (string) array_key_first($popped);
            if ($this->isOneTimeBannerExpired($member, $now)) {
                continue;
            }

            [$bannerId] = explode(':', $member);
            $banner = Banner::loadCachedBanner($this->redis, $bannerId);
            if (!$banner) {
                throw new \Exception("Banner with ID $bannerId is not present in cache");
            }
            return $banner;
        }
        return null;
    }

    private function isOneTimeBannerExpired(string $member, int $now): bool
    {
        [, $expiresAt] = explode(':', $member);
        return (int) $expiresAt <= $now;
    }

    private function checkUserInSegment(CampaignSegment $campaignSegment, $userId): bool
    {
        $cacheKey = SegmentAggregator::cacheKey($campaignSegment). '|user|'.$userId;
        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        $this->timer->increment('segment_checks');
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

        $this->timer->increment('segment_checks');
        $belongsToSegment = $this->segmentAggregator->checkBrowser($campaignSegment, (string)$browserId);
        $this->localCache[$cacheKey] = $belongsToSegment;

        return $belongsToSegment;
    }
}
