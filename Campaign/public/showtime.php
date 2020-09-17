<?php

use Airbrake\MonologHandler;
use App\Banner;
use App\Campaign;
use App\CampaignBanner;
use App\Http\Showtime\LazyDeviceDetector;
use App\Http\Showtime\LazyGeoReader;
use App\Http\Showtime\Showtime;
use App\Http\Showtime\ShowtimeResponse;
use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * asset overrides Laravel's helper function to prevent usage of Laravel's app()
 *
 * @param $path
 * @param null $secure
 * @return string
 */
function asset($path, $secure = null) {
    return '//' . $_SERVER['HTTP_HOST'] . '/' . trim($path, '/');
}

/**
 * public_path overrides Laravel's helper function to prevent usage of Laravel's app()
 *
 * @param string $path
 * @return string
 */
function public_path($path = '') {
    return __DIR__ .($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
}

class PlainPhpShowtimeResponse implements ShowtimeResponse
{
    /**
     * @param string $callback jsonp callback name
     * @param array $response response to be json-encoded and returned
     * @param int $statusCode http status code to be returned
     */
    private function jsonpResponse($callback, $response, $statusCode = 200) {
        http_response_code($statusCode);
        $params = json_encode($response);
        echo "{$callback}({$params})";
        exit;
    }

    public function error($callback, int $statusCode, array $errors)
    {
        $this->jsonpResponse($callback, [
            'success' => false,
            'errors' => $errors,
        ], $statusCode);
    }

    public function success(string $callback, $data, $activeCampaignUuids, $providerData)
    {
        $this->jsonpResponse($callback, [
            'success' => true,
            'errors' => [],
            'data' => empty($data) ? [] : $data,
            'activeCampaignIds' => $activeCampaignUuids,
            'providerData' => $providerData,
        ]);
    }

    public function renderCampaign(CampaignBanner $variant, Campaign $campaign, array $alignments, array $dimensions, array $positions): string {
        return $this->renderInternal($variant->banner, $variant->uuid, $campaign->uuid, (int) $variant->controlGroup, $alignments, $dimensions, $positions);
    }

    public function renderBanner(Banner $banner, array $alignments, array $dimensions, array $positions): string {
        return $this->renderInternal($banner, null, null, 0, $alignments, $dimensions, $positions);
    }

    private function renderInternal(
        Banner $banner,
        $variantUuid,
        $campaignUuid,
        $isControlGroup,
        $alignments,
        $dimensions,
        $positions) {

        $alignmentsJson = json_encode($alignments);
        $dimensionsJson = json_encode($dimensions);
        $positionsJson = json_encode($positions);

        $bannerJs = asset(mix('/js/banner.js', '/assets/lib'));

        if (!$banner ){
            $js = 'var bannerUuid = null;';
        } else {
            $js = "
var bannerUuid = '{$banner->uuid}';
var bannerId = 'b-' + bannerUuid;
var bannerJsonData = {$banner->toJson()};
";
        }

        if ($variantUuid) {
            $js .= "var variantUuid = '{$variantUuid}';\n";
        } else {
            $js .= "var variantUuid = null;\n";
        }

        if ($campaignUuid) {
            $js .= "var campaignUuid = '{$campaignUuid}';\n";
        } else {
            $js .= "var campaignUuid = null;\n";
        }

        $js .= <<<JS
var isControlGroup = {$isControlGroup}
var scripts = [];
if (typeof window.remplib.banner === 'undefined') {
    scripts.push("{$bannerJs}");
}

var styles = [];

var waiting = scripts.length + styles.length;
var run = function() {
    if (waiting) {
        return;
    }

    var banner = {};
    var alignments = JSON.parse('{$alignmentsJson}');
    var dimensions = JSON.parse('{$dimensionsJson}');
    var positions = JSON.parse('{$positionsJson}');

    if (!isControlGroup) {
        banner = remplib.banner.fromModel(bannerJsonData);
    }

    banner.show = false;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    banner.campaignUuid = campaignUuid;
    banner.variantUuid = variantUuid;
    banner.uuid = bannerUuid;
    
    if (typeof remplib.campaign.bannerUrlParams !== "undefined") {
        banner.urlParams = remplib.campaign.bannerUrlParams;
    }

    if (isControlGroup) {
        banner.displayDelay = 0;
        banner.displayType = 'none';
    } else {
        var d = document.createElement('div');
        d.id = bannerId;
        var bp = document.createElement('banner-preview');
        d.appendChild(bp);

        var target = null;
        if (banner.displayType === 'inline') {
            target = document.querySelector(banner.targetSelector);
            if (target === null) {
                console.warn("REMP: unable to display banner, selector not matched: " + banner.targetSelector);
                return;
            }
        } else {
            target = document.getElementsByTagName('body')[0];
        }
        target.appendChild(d);

        remplib.banner.bindPreview('#' + bannerId, banner);
    }

    setTimeout(function() {
        var event = {
            utm_source: "remp_campaign",
            utm_medium: banner.displayType,
            utm_content: banner.uuid
        };
        
        if (banner.campaignUuid) {
            event.utm_campaign = banner.campaignUuid; 
        }
        if (banner.variantUuid) {
            event.banner_variant = banner.variantUuid; 
        }
        
        if (!banner.manualEventsTracking) {
            remplib.tracker.trackEvent("banner", "show", null, null, event);    
        }
        
        banner.show = true;
        if (banner.closeTimeout) {
            setTimeout(function() {
                banner.show = false;
            }, banner.closeTimeout);
        }
        
        if (banner.campaignUuid && banner.variantUuid) {
            remplib.campaign.handleBannerDisplayed(banner.campaignUuid, banner.uuid, banner.variantUuid);    
        }
        
    }, banner.displayDelay);
};

for (var i=0; i<scripts.length; i++) {
    remplib.loadScript(scripts[i], function() {
        waiting -= 1;
        run();
    });
}
for (i=0; i<styles.length; i++) {
    remplib.loadStyle(styles[i], function() {
        waiting -= 1;
        run();
    });
}
JS;

        return $js;
    }
}

header('Content-Type: application/javascript');

$dotenv = new \Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$logger = new Logger('showtime');
try {
    $enabledAirbrake = env('AIRBRAKE_ENABLED', env('APP_ENV') !== 'local');
    if ($enabledAirbrake) {
        $airbrake = new \Airbrake\Notifier([
            'enabled' => true,
            'projectId' => '_',
            'projectKey' => env('AIRBRAKE_API_KEY', ''),
            'host' => env('AIRBRAKE_API_HOST', 'api.airbrake.io'),
            'environment' => env('APP_ENV', 'production'),
        ]);

        $logHandler = new MonologHandler($airbrake, Logger::WARNING);
        $logger->setHandlers([$logHandler]);
    }
} catch (\Exception $e) {
    $logger->warning('unable to register airbrake notifier: ' . $e->getMessage());
}

$data = filter_input(INPUT_GET, 'data');
$callback = filter_input(INPUT_GET, 'callback');

// dependencies initialization
$redis = new \Predis\Client([
    'scheme' => 'tcp',
    'host'   => getenv('REDIS_HOST'),
    'port'   => getenv('REDIS_PORT') ?: 6379,
    'password' => getenv('REDIS_PASSWORD') ?: null,
    'database' => getenv('REDIS_DEFAULT_DATABASE') ?: 0,
]);

$showtimeResponse = new PlainPhpShowtimeResponse();

/** @var \App\Contracts\SegmentAggregator $segmentAggregator */
$segmentAggregator = unserialize($redis->get(\App\Providers\AppServiceProvider::SEGMENT_AGGREGATOR_REDIS_KEY))();
if (!$segmentAggregator) {
    $showtimeResponse->error($callback, 500, ['unable to get cached segment aggregator']);
}

$deviceDetector = new LazyDeviceDetector($redis);
$maxmindDbPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . getenv('MAXMIND_DATABASE');
$geoReader = new LazyGeoReader($maxmindDbPath);

$showtime = new Showtime($redis, $segmentAggregator, $geoReader, $deviceDetector, $logger);
$showtime->showtime($data, $callback, $showtimeResponse);