<?php

use Monolog\Formatter\NormalizerFormatter;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Http\Showtime\DeviceRulesEvaluator;
use Remp\CampaignModule\Http\Showtime\LazyDeviceDetector;
use Remp\CampaignModule\Http\Showtime\LazyGeoReader;
use Remp\CampaignModule\Http\Showtime\Showtime;
use Remp\CampaignModule\Http\Showtime\ShowtimeResponse;
use Remp\CampaignModule\Http\Showtime\ShowtimeConfig;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Monolog\Logger;

require_once __DIR__ . '/../../../vendor/autoload.php';

/**
 * asset overrides Laravel's helper function to prevent usage of Laravel's app()
 *
 * @param $path
 * @param null $secure
 * @return string
 */
function asset($path, $secure = null) {
    return ((isSecure() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . trim($path, '/'));
}

function isSecure(): bool
{
    if ((isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] === 'on') || ($_SERVER['HTTPS'] === '1')))
        || (isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] === 443)) {
        return true;
    }

    if ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')) {
        return true;
    }

    return false;
}

/**
 * public_path overrides Laravel's helper function to prevent usage of Laravel's app()
 *
 * @param string $path
 * @return string
 */
function public_path($path = '') {
    return __DIR__ .'/../../'.($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
}

/**
 * Note: mix overrides Laravel's helper function to prevent usage of Laravel's app()
 * Get the path to a versioned Mix file.
 *
 * @param  string  $path
 * @param  string  $manifestDirectory
 * @return HtmlString|string
 *
 * @throws \Exception
 */
function mix($path, $manifestDirectory = '')
{
    static $manifests = [];

    if (! Str::startsWith($path, '/')) {
        $path = "/{$path}";
    }

    if ($manifestDirectory && ! Str::startsWith($manifestDirectory, '/')) {
        $manifestDirectory = "/{$manifestDirectory}";
    }

    if (file_exists(public_path($manifestDirectory.'/hot'))) {
        $url = rtrim(file_get_contents(public_path($manifestDirectory.'/hot')));

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return new HtmlString(Str::after($url, ':').$path);
        }

        return new HtmlString("//localhost:8080{$path}");
    }

    $manifestPath = public_path($manifestDirectory.'/mix-manifest.json');

    if (! isset($manifests[$manifestPath])) {
        if (! file_exists($manifestPath)) {
            throw new Exception('The Mix manifest does not exist.');
        }

        $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
    }

    $manifest = $manifests[$manifestPath];

    if (! isset($manifest[$path])) {
        $exception = new Exception("Unable to locate Mix file: {$path}.");

        if (! app('config')->get('app.debug')) {
            report($exception);

            return $path;
        } else {
            throw $exception;
        }
    }

    return new HtmlString($manifestDirectory.$manifest[$path]);
}

class PlainPhpShowtimeResponse implements ShowtimeResponse
{
    public function jsonResponse($response, $statusCode = 400) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($response);
        exit;
    }

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

    public function success(string $callback, $data, $activeCampaigns, $providerData, $suppressedBanners, array $evaluationMessages = [])
    {
        $responseData = [
            'success' => true,
            'errors' => [],
            'data' => empty($data) ? [] : $data,
            'activeCampaignIds' => array_column($activeCampaigns, 'uuid'),
            'activeCampaigns' => $activeCampaigns,
            'providerData' => $providerData,
            'suppressedBanners' => $suppressedBanners,
        ];

        if ($evaluationMessages) {
            $responseData['evaluationMessages'] = $evaluationMessages;
        }

        $this->jsonpResponse($callback, $responseData);
    }

    public function renderCampaign(CampaignBanner $variant, Campaign $campaign, array $alignments, array $dimensions, array $positions, array $colorSchemes, array $snippets, mixed $userData): string {
        return $this->renderInternal(
            banner: $variant->banner,
            alignments: $alignments,
            dimensions: $dimensions,
            positions: $positions,
            colorSchemes: $colorSchemes,
            snippets: $snippets,
            variantUuid: $variant->uuid,
            campaignUuid: $campaign->uuid,
            isControlGroup: (int) $variant->control_group,
            variantPublicId: $variant->public_id,
            campaignPublicId: $campaign->public_id,
            userData: $userData
        );
    }

    public function renderBanner(Banner $banner, array $alignments, array $dimensions, array $positions, array $colorSchemes, array $snippets): string {
        return $this->renderInternal(
            banner: $banner,
            alignments: $alignments,
            dimensions: $dimensions,
            positions: $positions,
            colorSchemes: $colorSchemes,
            snippets: $snippets,
        );
    }

    private function renderInternal(
        ?Banner $banner,
        $alignments,
        $dimensions,
        $positions,
        $colorSchemes,
        $snippets,
        $variantUuid = null,
        $campaignUuid = null,
        $isControlGroup = 0,
        $variantPublicId = null,
        $campaignPublicId = null,
        $userData = null
    ) {
        $alignmentsJson = json_encode($alignments);
        $dimensionsJson = json_encode($dimensions);
        $positionsJson = json_encode($positions);
        $colorSchemesJson = json_encode($colorSchemes);
        $snippetsJson = json_encode($snippets);
        $userDataJson = json_encode($userData);

        $bannerJs = asset(mix('/js/banner.js', '/assets/lib'));

        if (!$banner ){
            $js = 'var bannerUuid = null;';
        } else {
            $js = "
var bannerUuid = '{$banner->uuid}';
var bannerPublicId = '{$banner->public_id}';
var bannerId = 'b-' + bannerUuid;
var bannerJsonData = {$banner->toJson()};
";
        }

        if ($variantUuid) {
            $js .= "var variantUuid = '{$variantUuid}';\n";
            $js .= "var variantPublicId = '{$variantPublicId}';\n";
        } else {
            $js .= "var variantUuid = null;\n";
            $js .= "var variantPublicId = null;\n";
        }

        if ($campaignUuid) {
            $js .= "var campaignUuid = '{$campaignUuid}';\n";
            $js .= "var campaignPublicId = '{$campaignPublicId}';\n";
        } else {
            $js .= "var campaignUuid = null;\n";
            $js .= "var campaignPublicId = null;\n";
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
    var colorSchemes = JSON.parse('{$colorSchemesJson}');
    var snippets = {$snippetsJson};
    var userData = JSON.parse('{$userDataJson}');

    if (!isControlGroup) {
        var campaignUserData = null;
        if (userData && userData.campaigns && userData.campaigns[campaignPublicId]) {
            campaignUserData = userData.campaigns[campaignPublicId];
        }
        banner = remplib.banner.parseUserData(remplib.banner.fromModel(bannerJsonData), campaignUserData);
    }

    banner.show = false;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;
    banner.colorSchemes = colorSchemes;
    banner.snippets = snippets;

    banner.campaignUuid = campaignUuid;
    banner.campaignPublicId = campaignPublicId;
    banner.variantUuid = variantUuid;
    banner.variantPublicId = variantPublicId;
    
    if (bannerUuid) {
        banner.uuid = bannerUuid;
        banner.publicId = bannerPublicId;    
    }

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
            rtm_source: "remp_campaign",
            rtm_medium: banner.displayType,
            rtm_content: banner.uuid
        };

        if (banner.campaignUuid) {
            event.rtm_campaign = banner.campaignUuid;
        }
        if (banner.variantUuid) {
            event.rtm_variant = banner.variantUuid;
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
            remplib.campaign.handleBannerDisplayed(banner.campaignUuid, banner.uuid, banner.variantUuid, banner.campaignPublicId, banner.publicId, banner.variantPublicId);
        }

        if (typeof resolve !== "undefined") {
            resolve(true);
        }

    }, banner.displayDelay);
}

run();

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
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

$logger = new Logger('showtime');
$streamHandler = new \Monolog\Handler\StreamHandler(__DIR__ . '/../../../storage/logs/laravel.log');
$formatter = $streamHandler->getFormatter();
if ($formatter instanceof NormalizerFormatter) {
    $formatter->setDateFormat('Y-m-d H:i:s');
    $streamHandler->setFormatter($formatter);
}
$logger->pushHandler($streamHandler);

try {
    $sentryDSN = env('SENTRY_DSN');
    if (!empty($sentryDSN)) {
        $sentryClientOptions = [
            'dsn' => $sentryDSN,
            'environment' => env('APP_ENV', 'production'),
            'send_default_pii' => true, // enabled to see same details as with laravel sentry
        ];

        $sampleRate = env('SENTRY_SHOWTIME_SAMPLERATE', 1);
        if (is_numeric($sampleRate)) {
            $sentryClientOptions['sample_rate'] = (float) $sampleRate;
        } else {
            $logger->warning("invalid SENTRY_SHOWTIME_SAMPLERATE='{$sampleRate}' configured, defaulting to '1'.");
        }

        $client = \Sentry\ClientBuilder::create($sentryClientOptions)->getClient();
        $handler = new \Sentry\Monolog\Handler(new \Sentry\State\Hub($client));
        $logger->pushHandler($handler);
    }
} catch (\Exception $e) {
    $logger->warning('unable to register sentry notifier: ' . $e->getMessage());
}

$showtimeResponse = new PlainPhpShowtimeResponse();
$data = filter_input(INPUT_GET, 'data');
$callback = filter_input(INPUT_GET, 'callback');

$showtimeConfig = new ShowtimeConfig();
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $showtimeConfig->setAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
}
$showtimeConfig->setDebugKey(env('CAMPAIGN_DEBUG_KEY'));

if ($data === null || $callback === null) {
    $showtimeResponse->jsonResponse(['errors' => ['invalid request, data or callback params missing']]);
    return;
}

header('Content-Type: application/javascript');

try {
    // dependencies initialization
    if (env('REDIS_CLIENT', 'predis') === 'phpredis') {
        $redis = new \Redis();
        if (env('REDIS_PERSISTENT', false)) {
            $redis->pconnect(env('REDIS_HOST'), (int) env('REDIS_PORT', 6379), 5, 'showtime-'.env('REDIS_DEFAULT_DATABASE'));
        } else {
            $redis->connect(env('REDIS_HOST'), (int) env('REDIS_PORT', 6379), 5);
        }
        if ($pwd = env('REDIS_PASSWORD')) {
            $redis->auth($pwd);
        }
        $redis->setOption(\Redis::OPT_PREFIX, env('REDIS_PREFIX', ''));
        $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        $redis->select((int) env('REDIS_DEFAULT_DATABASE', 0));

    } else {
        $redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => env('REDIS_HOST'),
            'port'   => env('REDIS_PORT') ?: 6379,
            'password' => env('REDIS_PASSWORD') ?: null,
            'database' => env('REDIS_DEFAULT_DATABASE') ?: 0,
            'persistent' => env('REDIS_PERSISTENT', false),
        ], [
            'prefix' => env('REDIS_PREFIX') ?: '',
        ]);
    }

    $segmentAggregator = SegmentAggregator::unserializeFromRedis($redis);
    if (!$segmentAggregator) {
        $logger->error("unable to get cached segment aggregator, have you run 'campaigns:refresh-cache' command?");
        $showtimeResponse->error($callback, 500, ['Internal error, application might not have been correctly initialized.']);
    }

    if (extension_loaded('apcu')) {
        $cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
            new \MatthiasMullie\Scrapbook\Adapters\Apc(),
        );
    } elseif (env('REDIS_CLIENT', 'predis') === 'phpredis') {
        $cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
            new \MatthiasMullie\Scrapbook\Adapters\Redis($redis),
        );
    } else {
        $cache = new \Kodus\PredisSimpleCache\PredisSimpleCache($redis, 60*60*24);
    }

    $deviceDetector = new LazyDeviceDetector($cache);
    $deviceRulesEvaluator = new DeviceRulesEvaluator($redis, $deviceDetector);

    if (file_exists(env('MAXMIND_DATABASE'))) {
        $maxmindDbPath = env('MAXMIND_DATABASE');
    } else {
        $maxmindDbPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . env('MAXMIND_DATABASE');
    }
    $geoReader = new LazyGeoReader($maxmindDbPath);

    $prioritizeBannerOnSamePosition = filter_var(
        env('PRIORITIZE_BANNERS_ON_SAME_POSITION', false),
        FILTER_VALIDATE_BOOLEAN,
        ['options' => ['default' => false]]
    );
    $showtimeConfig->setPrioritizeBannerOnSamePosition($prioritizeBannerOnSamePosition);
    $showtimeConfig->setOneTimeBannerEnabled(env('ONE_TIME_BANNER_ENABLED', true));

    $showtime = new Showtime($redis, $segmentAggregator, $geoReader, $showtimeConfig, $deviceRulesEvaluator, $logger);
    $showtime->showtime($data, $callback, $showtimeResponse);
} catch (\Exception $exception) {
    $logger->error($exception);
    $showtimeResponse->error($callback, 500, ['Internal error, unable to display campaigns.']);
}
