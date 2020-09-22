<?php

namespace Tests\Feature;

use App\Banner;
use App\Campaign;
use App\CampaignBanner;
use App\CampaignSegment;
use App\Contracts\SegmentAggregator;
use App\Http\Request;
use App\Http\Showtime\LazyDeviceDetector;
use App\Http\Showtime\LazyGeoReader;
use App\Http\Showtime\Showtime;
use App\Schedule;
use App\ShortMessageTemplate;
use CountrySeeder;
use Faker\Provider\Base;
use Faker\Provider\Uuid;
use Illuminate\Support\Carbon;
use Mockery;
use Monolog\Logger;
use Predis\ClientInterface;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShowtimeTest extends TestCase
{
    use RefreshDatabase;

    /** @var Showtime */
    protected $showtime;

    protected $segmentAggregator;

    /** @var Campaign */
    protected $campaign;

    /** @var CampaignBanner */
    protected $campaignBanner;

    protected function setUp()
    {
        parent::setUp();

        // Prepare showtime
        $redis = resolve(ClientInterface::class);

        $this->segmentAggregator = Mockery::mock(SegmentAggregator::class);
        $geoReader = Mockery::mock(LazyGeoReader::class);
        $geoReader->shouldReceive('countryCode')->andReturn('SK');
        $logger = Mockery::mock(Logger::class);

        $showtime = new Showtime($redis, $this->segmentAggregator, $geoReader, resolve(LazyDeviceDetector::class), $logger);
        $showtime->setDimensionMap(resolve(\App\Models\Dimension\Map::class));
        $showtime->setAlignmentsMap(resolve(\App\Models\Alignment\Map::class));
        $showtime->setPositionMap(resolve(\App\Models\Position\Map::class));
        $this->showtime = $showtime;

        // Prepare banner and campaign
        $banner = factory(Banner::class)->create(['template' => 'short_message']);
        factory(ShortMessageTemplate::class)->create(['banner_id' => $banner->id]);

        $campaign = factory(Campaign::class)->create([
            'once_per_session' => false,
            'signed_in' => null,
            'using_adblock' => null,
            'url_filter' => 'everywhere',
            'referer_filter' => 'everywhere',
            'devices' => [Campaign::DEVICE_DESKTOP, Campaign::DEVICE_MOBILE],
            'pageview_rules' => null,
        ]);
        $this->campaignBanner = factory(CampaignBanner::class)->create([
            'campaign_id' => $campaign->id,
            'banner_id' => $banner->id,
            'control_group' => 0,
            'proportion' => 100,
            'weight' => 1
        ]);
        factory(CampaignBanner::class)->create([
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
            'campaignsSession' => $campaignsSession,
            'userAgent' => $isDesktop ? $desktopUa : $mobileUa
        ];

        return json_decode(json_encode($d));
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
        $campaign = factory(Campaign::class)->create();
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
        $this->assertEquals($this->campaign->uuid, $activeCampaignUuids[0]);

        $activeCampaignUuids = [];
        $campaignsSession = [$this->campaign->uuid => ['seen' => 1]];
        $userData = $this->getUserData(null, null, null, true, null, $campaignsSession);
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

    public function testRefererFilters()
    {
        $this->scheduleCampaign();
        $activeCampaignUuids = [];

        $this->campaign->update([
            'referer_filter' => Campaign::URL_FILTER_ONLY_AT,
            'referer_patterns' => ['facebook.com']
        ]);

        $userData = $this->getUserData();
        $userData->referer = 'http://facebook.com/abcd';
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData->referer = 'twitter.com/realDonaldTrump';
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $this->campaign->update([
            'referer_filter' => Campaign::URL_FILTER_EXCEPT_AT,
            'referer_patterns' => ['facebook.com']
        ]);

        $userData->referer = 'http://facebook.com/abcd';
        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));

        $userData->referer = 'twitter.com/realDonaldTrump';
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
        $this->segmentAggregator->shouldReceive('checkUser')->andReturn(false, true);

        $this->assertNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(0, $activeCampaignUuids);
        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
        $this->assertCount(1, $activeCampaignUuids);

        $campaignSegment->update(['inclusive' => 0]);
        $this->campaign->load('segments');
        $userData->userId = null;

        $this->segmentAggregator->shouldReceive('checkBrowser')->andReturn(false, true);

        $this->assertNotNull($this->showtime->shouldDisplay($this->campaign, $userData, $activeCampaignUuids));
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
}
