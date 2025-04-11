<?php

namespace Remp\CampaignModule\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Remp\CampaignModule\Database\Seeders\CountrySeeder;
use Remp\CampaignModule\Http\Showtime\DeviceRulesEvaluator;
use Remp\CampaignModule\Http\Showtime\LazyDeviceDetector;
use Remp\CampaignModule\Http\Showtime\ShowtimeConfig;
use Remp\CampaignModule\Http\Showtime\ShowtimeTestable;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Http\Showtime\LazyGeoReader;
use Remp\CampaignModule\Schedule;
use Remp\CampaignModule\ShortMessageTemplate;

use Faker\Provider\Base;
use Faker\Provider\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Mockery;
use Monolog\Logger;
use Predis\ClientInterface;
use Remp\CampaignModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShowtimeTest extends TestCase
{
    use RefreshDatabase;

    protected ShowtimeTestable $showtime;
    protected $segmentAggregator;
    protected Campaign $campaign;
    protected CampaignBanner $campaignBanner;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare showtime
        $redis = resolve(ClientInterface::class);

        $this->segmentAggregator = Mockery::mock(SegmentAggregator::class);
        $geoReader = Mockery::mock(LazyGeoReader::class);
        $geoReader->shouldReceive('countryCode')->andReturn('SK');
        $logger = Mockery::mock(Logger::class);

        $showtimeConfig = new ShowtimeConfig();
        $deviceDetectionRules = new DeviceRulesEvaluator($redis, resolve(LazyDeviceDetector::class));
        $showtime = new ShowtimeTestable($redis, $this->segmentAggregator, $geoReader, $showtimeConfig, $deviceDetectionRules, $logger);
        $showtime->setDimensionMap(resolve(\Remp\CampaignModule\Models\Dimension\Map::class));
        $showtime->setAlignmentsMap(resolve(\Remp\CampaignModule\Models\Alignment\Map::class));
        $showtime->setPositionMap(resolve(\Remp\CampaignModule\Models\Position\Map::class));
        $showtime->setColorSchemesMap(resolve(\Remp\CampaignModule\Models\ColorScheme\Map::class));
        $this->showtime = $showtime;

        // Prepare banner and campaign
        $banner = $this->prepareBanner();

        $campaign = $this->prepareCampaign();

        $this->campaignBanner = $this->prepareCampaignBanners($campaign,$banner);

        CampaignBanner::factory()->create([
            'campaign_id' => $campaign->id,
            'control_group' => 1,
            'proportion' => 0,
            'weight' => 2
        ]);
        $this->campaign = $campaign;
    }

    private function scheduleCampaign()
    {
        Schedule::create([
            'start_time' => Carbon::now(),
            'status' => Schedule::STATUS_EXECUTED,
            'campaign_id' => $this->campaign->id,
        ]);
    }

    // data being sent by user's remplib
    private function getUserData(
        $url = null,
        $userId = null,
        $browserId = null,
        $isDesktop = true,
        $campaigns = null,
        $language = null,
        $campaignsSession = null
    ) {
        if (!$url) {
            $url = 'test.example';
        }

        if (!$userId) {
            $userId = Base::randomNumber(5);
        }
        if (!$browserId) {
            $browserId = Uuid::uuid();
        }

        $desktopUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246';
        $mobileUa = 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36';

        $d = [
            'url' => $url,
            'userId' => $userId,
            'browserId' => $browserId,
            'campaigns' => $campaigns,
            'language' => $language,
            'campaignsSession' => $campaignsSession,
            'userAgent' => $isDesktop ? $desktopUa : $mobileUa
        ];

        return json_decode(json_encode($d));
    }

    public function testPageviewAttributesFilter()
    {
        $this->scheduleCampaign();
        $userData = $this->getUserData();

        $activeCampaignUuids = [];
        $userData->pageviewAttributes = [];
        $bannerVariant = $this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids);
        $this->assertNotNull($bannerVariant);

        $this->campaign->update([
            'pageview_attributes' => [
                [
                    'name' => 'author',
                    'operator' => '=',
                    'value' => 'author_value_1'
                ],
                [
                    'name' => 'category',
                    'operator' => '=',
                    'value' => 'category_value_1',
                ],
            ]
        ]);

        $activeCampaignUuids = [];
        $bannerVariant = $this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids);
        $this->assertNull($bannerVariant);

        $userData->pageviewAttributes = json_decode(json_encode([
            'author' => 'author_value_1',
            'category' => [
                'category_value_1',
                'category_value_2',
            ],
        ]), false);

        $activeCampaignUuids = [];
        $bannerVariant = $this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids);
        $this->assertNotNull($bannerVariant);
        $this->assertNotEmpty($activeCampaignUuids);

        $this->campaign->update([
            'pageview_attributes' => [
                [
                    'name' => 'author',
                    'operator' => '!=',
                    'value' => 'author_value_1'
                ],
                [
                    'name' => 'category',
                    'operator' => '!=',
                    'value' => 'category_value_1',
                ],
            ]
        ]);

        $userData->pageviewAttributes = json_decode(json_encode([
            'author' => 'author_value_2',
            'category' => [
                'category_value_2',
                'category_value_3',
            ],
        ]), false);

        $activeCampaignUuids = [];
        $bannerVariant = $this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids);
        $this->assertNotNull($bannerVariant);
        $this->assertNotEmpty($activeCampaignUuids);

        $userData->pageviewAttributes = json_decode(json_encode([
            'author' => 'author_value_1',
            'category' => [
                'category_value_2',
                'category_value_3',
            ],
        ]), false);

        $activeCampaignUuids = [];
        $bannerVariant = $this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids);
        $this->assertNull($bannerVariant);

        $userData->pageviewAttributes = json_decode(json_encode([
            'author' => 'author_value_2',
            'category' => [
                'category_value_1',
                'category_value_3',
            ],
        ]), false);

        $activeCampaignUuids = [];
        $bannerVariant = $this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids);
        $this->assertNull($bannerVariant);
    }

    public function testStoppedCampaign()
    {
        $activeCampaignUuids = [];
        $data = $this->getUserData();
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $data, $activeCampaignUuids));
        $this->assertEmpty($activeCampaignUuids);
    }

    public function testCampaignWithoutBanners()
    {
        $data = $this->getUserData();
        $activeCampaignUuids = [];
        $campaign = Campaign::factory()->create();
        $this->assertNull($this->showtime->shouldDisplay($campaign, $data, $activeCampaignUuids));
        $this->assertEmpty($activeCampaignUuids);
    }

    public function testCampaignOncePerSession()
    {
        $this->campaign->update([
            'once_per_session' => 1
        ]);

        $this->scheduleCampaign();

        $activeCampaignUuids = [];
        $userData = $this->getUserData();
        $bannerVariant = $this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids);
        $this->assertNotNull($bannerVariant);
        $this->assertEquals($this->campaignBanner->id, $bannerVariant->id);
        $this->assertCount(1, $activeCampaignUuids);
        $this->assertEquals($this->campaign->uuid, $activeCampaignUuids[0]['uuid']);

        $activeCampaignUuids = [];
        $campaignsSession = [$this->campaign->uuid => ['seen' => 1]];
        $userData = $this->getUserData(null, null, null, true, null, null, $campaignsSession);
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertEmpty($activeCampaignUuids);
    }

    public function testCampaignSignedInUser()
    {
        $this->campaign->update([
            'signed_in' => 1
        ]);
        $this->scheduleCampaign();

        $activeCampaignUuids = [];
        $userData = $this->getUserData();
        $userData->userId = null;
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(0, $activeCampaignUuids);

        $userData->userId = 1;
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);
    }

    public function testAdBlock()
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];
        $userData = $this->getUserData();

        $this->campaign->update([
            'using_adblock' => 1
        ]);
        $userData->usingAdblock = false;
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(0, $activeCampaignUuids);
        $userData->usingAdblock = true;
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $this->campaign->update([
            'using_adblock' => 0
        ]);
        $userData->usingAdblock = true;
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $userData->usingAdblock = false;
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
    }

    public function testUrlFilters()
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        $this->campaign->update([
            'url_filter' => Campaign::URL_FILTER_ONLY_AT,
            'url_patterns' => ['dennikn.sk/1315683', 'minuta']
        ]);

        $userData = $this->getUserData('dennikn.sk/1315683');
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData = $this->getUserData('dennikn.sk/99999');
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData = $this->getUserData('dennikn.sk/minuta/1');
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $this->campaign->update([
            'url_filter' => Campaign::URL_FILTER_EXCEPT_AT,
            'url_patterns' => ['minuta']
        ]);

        $userData = $this->getUserData('dennikn.sk/99999');
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData = $this->getUserData('dennikn.sk/minuta/1');
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
    }

    public function testTraficSourceFilters()
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        // test referer source
        $this->campaign->update([
            'source_filter' => Campaign::SOURCE_FILTER_REFERER_ONLY_AT,
            'source_patterns' => ['facebook.com'],
        ]);

        $userData = $this->getUserData();
        $userData->referer = 'http://facebook.com/abcd';
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData->referer = 'twitter.com/realDonaldTrump';
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $this->campaign->update([
            'source_filter' => Campaign::SOURCE_FILTER_REFERER_EXCEPT_AT,
            'source_patterns' => ['facebook.com']
        ]);

        $userData->referer = 'http://facebook.com/abcd';
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData->referer = 'twitter.com/realDonaldTrump';
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        // test session source
        $this->campaign->update([
            'source_filter' => Campaign::SOURCE_FILTER_SESSION_ONLY_AT,
            'source_patterns' => ['facebook.com'],
        ]);

        $userData = $this->getUserData();
        $userData->sessionReferer = 'http://facebook.com/abcd';
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData->sessionReferer = 'twitter.com/realDonaldTrump';
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $this->campaign->update([
            'source_filter' => Campaign::SOURCE_FILTER_SESSION_EXCEPT_AT,
            'source_patterns' => ['facebook.com']
        ]);

        $userData->sessionReferer = 'http://facebook.com/abcd';
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData->sessionReferer = 'twitter.com/realDonaldTrump';
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
    }

    public function testDeviceRules()
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        $this->campaign->update([
            'devices' => [Campaign::DEVICE_MOBILE],
        ]);

        $userData = $this->getUserData(null, null, null, true);
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData = $this->getUserData(null, null, null, false);
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
    }

    public static function operatingSystemsDataProvider()
    {
        return [
            [
                [Campaign::OPERATING_SYSTEM_ANDROID],
                [
                    // Samsung Galaxy S22 5G
                    'Mozilla/5.0 (Linux; Android 13; SM-S901B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36' => true,
                    // iPhone 12
                    'Mozilla/5.0 (iPhone13,2; U; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1' => false,
                    // Edge on Win 10
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246' => false,
                ],
            ],
            [
                [Campaign::OPERATING_SYSTEM_IOS, Campaign::OPERATING_SYSTEM_WINDOWS],
                [
                    // iPhone 12
                    'Mozilla/5.0 (iPhone13,2; U; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1' => true,
                    // Edge on Win 10
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246' => true,
                    // Samsung Galaxy S22 5G
                    'Mozilla/5.0 (Linux; Android 13; SM-S901B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36' => false,
                ],
            ],
            [
                [Campaign::OPERATING_SYSTEM_MAC],
                [
                    // Safari on Mac OSX
                    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9' => true,
                    // Edge on Win 10
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246' => false,
                    // iPhone 12
                    'Mozilla/5.0 (iPhone13,2; U; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1' => false,
                ],
            ],
            [
                [],
                [
                    // Safari on Mac OSX
                    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9' => true,
                    // Edge on Win 10
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246' => true,
                    // iPhone 12
                    'Mozilla/5.0 (iPhone13,2; U; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1' => true,
                ],
            ],
            [
                [],
                [
                    null => true,
                ],
            ],
            [
                [Campaign::OPERATING_SYSTEM_MAC],
                [
                    null => false,
                ],
            ],
        ];
    }

    #[DataProvider('operatingSystemsDataProvider')]
    public function testOperatingSystemRules(array $operatingSystems, array $userAgents)
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        $this->campaign->update([
            'operating_systems' => empty($operatingSystems) ? null : $operatingSystems,
        ]);

        $userData = $this->getUserData(null, null, null, true);
        foreach ($userAgents as $userAgent => $shouldMatch) {
            if ($userAgent === null) {
                unset($userData->userAgent);
            } else {
                $userData->userAgent = $userAgent;
            }

            if ($shouldMatch) {
                $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
            } else {
                $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
            }
        }
    }

    public function testCountryRules()
    {
        $this->seed(CountrySeeder::class);
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        // Mocked geo reader returns SK country code by default for all IPs
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('ip')->andReturn('1.1.1.1');
        $this->showtime->setRequest($request);

        $this->campaign->countries()->sync([
            'SK' => ['blacklisted' => 0]
        ]);
        $userData = $this->getUserData();
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $this->campaign->countries()->sync([
            'SK' => ['blacklisted' => 1]
        ]);
        $this->campaign->load(['countries', 'countriesBlacklist', 'countriesWhitelist']);
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
    }

    public function testLanguageRules()
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        $this->campaign->update(['languages' => ["cs", "sk"]]);

        $userData = $this->getUserData(language: 'sk-SK');
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData = $this->getUserData(language: 'en-US');
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
    }

    public function testSegmentRules()
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        $campaignSegment = CampaignSegment::create([
            'campaign_id' => $this->campaign->id,
            'code' => 'test_segment',
            'provider' => 'remp_segment',
            'inclusive' => 1
        ]);

        $userData = $this->getUserData();

        $this->segmentAggregator->shouldReceive('cacheEnabled')->andReturn(false, false);
        $this->segmentAggregator->shouldReceive('checkUser')->andReturn(false, true);

        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(0, $activeCampaignUuids);
        $this->showtime->flushLocalCache();
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $campaignSegment->update(['inclusive' => 0]);
        $this->campaign->load('segments');
        $userData->userId = null;

        $this->segmentAggregator->shouldReceive('checkBrowser')->andReturn(false, true);

        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->showtime->flushLocalCache();
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
    }

    public function testPageviewRules()
    {
        $this->scheduleCampaign();

        // Always rule
        $this->campaign->update([
            'pageview_rules' => [
                'display_banner' => 'always'
            ]
        ]);

        $campaignsData = [$this->campaign->uuid => ['count' => 1, 'seen' => 0]];
        $userData = $this->getUserData(null, null, null, true, $campaignsData);
        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        // Every rule
        $this->campaign->update([
            'pageview_rules' => [
                'display_banner' => 'every',
                'display_banner_every' => 3
            ]
        ]);

        $campaignsData = [$this->campaign->uuid => ['count' => 1, 'seen' => 0]];
        $userData = $this->getUserData(null, null, null, true, $campaignsData);
        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids); // campaign should still be counted as active

        $campaignsData = [$this->campaign->uuid => ['count' => 3, 'seen' => 0]];
        $userData = $this->getUserData(null, null, null, true, $campaignsData);
        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids); // campaign should still be counted as active

    }

    public function testPageviewRulesAfterClickRule()
    {
        $this->scheduleCampaign();
        $campaignsDataClicked = [
            $this->campaign->uuid => [
                'count' => 1,
                'seen' => 0,
                'clickedAt' => Carbon::now()->subHours(2)->getTimestamp(),
            ],
        ];
        $campaignsSessionDataClicked = [
            $this->campaign->uuid => [
                'clickedAt' => Carbon::now()->subHours(2)->getTimestamp(),
            ],
        ];
        $userDataClicked = $this->getUserData(campaigns: $campaignsDataClicked, campaignsSession: $campaignsSessionDataClicked);
        $campaignsDataNoClick = [$this->campaign->uuid => ['count' => 1, 'seen' => 0]];
        $userDataNoClick = $this->getUserData(campaigns: $campaignsDataNoClick);

        // ALWAYS rule
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_clicked_display' => 'always',
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataClicked, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClick, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  NEVER rule
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_clicked_display' => 'never',
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userDataClicked, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClick, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  NOT IN SESSION rule
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_clicked_display' => 'never_in_session',
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userDataClicked, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClick, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  CLOSE FOR HOURS rule - should display
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_clicked_display' => 'close_for_hours',
                'after_clicked_hours' => 1
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataClicked, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClick, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  CLOSE FOR HOURS rule - should not display
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_clicked_display' => 'close_for_hours',
                'after_clicked_hours' => 3
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userDataClicked, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);
    }

    public function testPageviewRulesAfterCloseRule()
    {
        $this->scheduleCampaign();
        $campaignsDataClosed = [
            $this->campaign->uuid => [
                'count' => 1,
                'seen' => 0,
                'closedAt' => Carbon::now()->subHours(2)->getTimestamp(),
            ],
        ];
        $campaignsSessionDataClosed = [
            $this->campaign->uuid => [
                'closedAt' => Carbon::now()->subHours(2)->getTimestamp(),
            ],
        ];
        $userDataClosed = $this->getUserData(campaigns: $campaignsDataClosed, campaignsSession: $campaignsSessionDataClosed);
        $campaignsDataNoClose = [$this->campaign->uuid => ['count' => 1, 'seen' => 0]];
        $userDataNoClose = $this->getUserData(campaigns: $campaignsDataNoClose);

        // ALWAYS rule
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_closed_display' => 'always',
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataClosed, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClose, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  NEVER rule
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_closed_display' => 'never',
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userDataClosed, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClose, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  NOT IN SESSION rule
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_closed_display' => 'never_in_session',
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userDataClosed, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClose, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  CLOSE FOR HOURS rule - should display
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_closed_display' => 'close_for_hours',
                'after_closed_hours' => 1
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataClosed, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userDataNoClose, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        //  CLOSE FOR HOURS rule - should not display
        $this->campaign->update([
            'pageview_rules' => [
                'after_banner_closed_display' => 'close_for_hours',
                'after_closed_hours' => 3
            ]
        ]);

        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userDataClosed, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);
    }

    public function testSeenCountRules()
    {
        $this->scheduleCampaign();

        $this->campaign->update([
            'pageview_rules' => [
                'display_times' => 1,
                'display_n_times' => 2
            ]
        ]);

        $campaignsData = [$this->campaign->uuid => ['seen' => 1, 'count' => 0]];
        $userData = $this->getUserData(null, null, null, true, $campaignsData);
        $activeCampaignUuids = [];
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $campaignsData = [$this->campaign->uuid => ['seen' => 2, 'count' => 0]];
        $userData = $this->getUserData(null, null, null, true, $campaignsData);
        $activeCampaignUuids = [];
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);
    }

    public function testPrioritizeCampaignBannerOnDifferentPosition(): void
    {
        $campaign1 = $this->prepareCampaign();
        $campaign2 = $this->prepareCampaign();

        $campaigns = [$campaign1->id => $campaign1, $campaign2->id => $campaign2];

        $campaignBanner1 = $this->prepareCampaignBanners($campaign1, $this->prepareBanner(Banner::POSITION_TOP_LEFT, Banner::DISPLAY_TYPE_INLINE, '#test-id'));
        $campaignBanner2 = $this->prepareCampaignBanners($campaign2, $this->prepareBanner(Banner::POSITION_BOTTOM_LEFT, Banner::DISPLAY_TYPE_OVERLAY));

        $campaignBanners = [$campaignBanner1, $campaignBanner2];

        // banners on different position - both should be displayed
        $activeCampaigns = [];
        $suppressedBanners = [];
        $result = $this->showtime->prioritizeCampaignBannerOnPosition($campaigns, $campaignBanners, $activeCampaigns, $suppressedBanners);
        $this->assertCount(2, $result);
        $this->assertEmpty($suppressedBanners);
    }

    public function testPrioritizeCampaignBannerOnSamePosition(): void
    {
        $campaign1 = $this->prepareCampaign();
        $campaign2 = $this->prepareCampaign();

        $campaign2->setAttribute('updated_at', $campaign1->updated_at->addMinutes(10));

        $campaigns = [$campaign1->id => $campaign1, $campaign2->id => $campaign2];

        $campaignBanner1 = $this->prepareCampaignBanners($campaign1, $this->prepareBanner(Banner::POSITION_BOTTOM_LEFT, Banner::DISPLAY_TYPE_INLINE, '#test-id'));
        $campaignBanner2 = $this->prepareCampaignBanners($campaign2, $this->prepareBanner(Banner::POSITION_BOTTOM_LEFT, Banner::DISPLAY_TYPE_INLINE, '#test-id'));

        $campaignBanners = [$campaignBanner1, $campaignBanner2];
        $activeCampaigns = [
            ['uuid' => $campaign1->uuid, 'public_id' => $campaign1->public_id],
            ['uuid' => $campaign2->uuid, 'public_id' => $campaign2->public_id],
        ];
        $suppressedBanners = [];

        // campaigns with the same amount of banners - banner with more recent campaign (campaign2) update time should be prioritized
        $result = $this->showtime->prioritizeCampaignBannerOnPosition($campaigns, $campaignBanners, $activeCampaigns, $suppressedBanners);
        $this->assertCount(1, $result);
        $this->assertEquals($campaignBanner2, $result[0]);
        $this->assertCount(1, $activeCampaigns);
        $this->assertEquals($campaign2->uuid, array_pop($activeCampaigns)['uuid']);
        $this->assertCount(1, $suppressedBanners);
        $this->assertEquals([
            'campaign_banner_public_id' => $campaignBanner1->public_id,
            'banner_public_id' => $campaignBanner1->banner->public_id,
            'campaign_public_id' => $campaign1->public_id,
            'suppressed_by_campaign_public_id' => $campaign2->public_id,
        ], array_pop($suppressedBanners));


        // campaign1 has more banners so banner from campaign1 should be prioritized
        $campaignBanner3 = $this->prepareCampaignBanners($campaign1, $this->prepareBanner());
        $campaign1->campaignBanners->push($campaignBanner3);

        $campaigns = [$campaign1->id => $campaign1, $campaign2->id => $campaign2];

        $activeCampaigns = [
            ['uuid' => $campaign1->uuid, 'public_id' => $campaign1->public_id],
            ['uuid' => $campaign2->uuid, 'public_id' => $campaign2->public_id],
        ];
        $suppressedBanners = [];
        $result = $this->showtime->prioritizeCampaignBannerOnPosition($campaigns, $campaignBanners, $activeCampaigns, $suppressedBanners);
        $this->assertCount(1, $result);
        $this->assertEquals($campaignBanner1, $result[0]);
        $this->assertCount(1, $activeCampaigns);
        $this->assertEquals($campaign1->uuid, array_pop($activeCampaigns)['uuid']);
        $this->assertCount(1, $suppressedBanners);
        $this->assertEquals([
            'campaign_banner_public_id' => $campaignBanner2->public_id,
            'banner_public_id' => $campaignBanner2->banner->public_id,
            'campaign_public_id' => $campaign2->public_id,
            'suppressed_by_campaign_public_id' => $campaign1->public_id,
        ], array_pop($suppressedBanners));
    }

    private function prepareCampaign(): Campaign
    {
        return Campaign::factory()->create([
            'once_per_session' => false,
            'signed_in' => null,
            'using_adblock' => null,
            'url_filter' => 'everywhere',
            'source_filter' => 'everywhere',
            'devices' => [Campaign::DEVICE_DESKTOP, Campaign::DEVICE_MOBILE],
            'pageview_rules' => null,
        ]);
    }

    private function prepareCampaignBanners(Campaign $campaign, Banner $banner): CampaignBanner
    {
        return CampaignBanner::factory()->create([
            'campaign_id' => $campaign->id,
            'banner_id' => $banner->id,
            'control_group' => 0,
            'proportion' => 100,
            'weight' => 1
        ]);
    }

    private function prepareBanner(
        string $position = Banner::POSITION_BOTTOM_LEFT,
        string $displayType = Banner::DISPLAY_TYPE_INLINE,
        string $targetSelector = null
    ): Banner
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_SHORT_MESSAGE,
            'position' => $position,
            'display_type' => $displayType,
            'target_selector' => $targetSelector,
        ]);

        ShortMessageTemplate::factory()->create(['banner_id' => $banner->id]);

        return $banner;
    }
}
