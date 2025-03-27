<?php

namespace Remp\CampaignModule\Http\Showtime;

use DeviceDetector\DeviceDetector;
use Predis\ClientInterface;
use Remp\CampaignModule\Campaign;

class DeviceRulesEvaluator
{
    private const DEVICE_DETECTION_KEY = 'device_detection';
    private const OPERATING_SYSTEM_DETECTION_KEY = 'operating_system_detection';
    private const DEVICE_DETECTION_TTL = 86400;

    private array $localCache = [];

    /** @var DeviceDetector[] */
    private array $deviceDetectorResults = [];

    public function __construct(
        private readonly ClientInterface|\Redis $redis,
        private readonly LazyDeviceDetector $deviceDetector,
    ) {
    }

    public function isAcceptedByDeviceRules(string $userAgent, Campaign $campaign): bool
    {
        if (!in_array(Campaign::DEVICE_MOBILE, $campaign->devices, true)
            && !in_array(Campaign::DEVICE_DESKTOP, $campaign->devices, true)
        ) {
            return true;
        }

        // check result of device detection in redis
        $deviceKey = self::DEVICE_DETECTION_KEY.':'.md5($userAgent);
        $deviceDetection = $this->resolveCachedValue($deviceKey);

        if ($deviceDetection) {
            if ($deviceDetection === Campaign::DEVICE_MOBILE && !in_array(Campaign::DEVICE_MOBILE, $campaign->devices, true)) {
                return false;
            }
            if ($deviceDetection === Campaign::DEVICE_DESKTOP && !in_array(Campaign::DEVICE_DESKTOP, $campaign->devices, true)) {
                return false;
            }

            return true;
        }

        // resolve device
        $deviceDetectorResult = $this->resolveDevice($userAgent);
        $isMobile = $deviceDetectorResult->isMobile();
        $isDesktop = $deviceDetectorResult->isDesktop();

        if ($isMobile) {
            $deviceDetectionValue = Campaign::DEVICE_MOBILE;
        } else if ($isDesktop) {
            $deviceDetectionValue = Campaign::DEVICE_DESKTOP;
        } else {
            $deviceDetectionValue = 'other';
        }

        // store result to redis to faster resolution on the next attempt
        $this->localCache[$deviceKey] = $deviceDetectionValue;
        $this->redis->setex($deviceKey, self::DEVICE_DETECTION_TTL, $deviceDetectionValue);

        if ($isMobile && !in_array(Campaign::DEVICE_MOBILE, $campaign->devices, true)) {
            return false;
        }

        if ($isDesktop && !in_array(Campaign::DEVICE_DESKTOP, $campaign->devices, true)) {
            return false;
        }

        return true;
    }

    public function isAcceptedByOperatingSystemRules(string $userAgent, Campaign $campaign): bool
    {
        $allowedOperatingSystems = $campaign->operating_systems;
        if (empty($allowedOperatingSystems)) {
            return true;
        }

        // check result of operating system detection in redis
        $osKey = self::OPERATING_SYSTEM_DETECTION_KEY.':'.md5($userAgent);
        $osDetection = $this->resolveCachedValue($osKey);
        if ($osDetection) {
            if ($osDetection === Campaign::OPERATING_SYSTEM_UNKNOWN
                || !in_array($osDetection, $allowedOperatingSystems, true)
            ) {
                return false;
            }
            return true;
        }

        // resolve operating system
        $deviceDetectorResult = $this->resolveDevice($userAgent);
        $detectedOs = $deviceDetectorResult->getOs('short_name');

        $osMapping = $campaign->getOperatingSystemsMapping();
        foreach ($osMapping as $osValue => $osShortCodes) {
            if (in_array($detectedOs, $osShortCodes, true)) {
                $osDetection = $osValue;
            }
        }

        if (empty($osDetection)) {
            $osDetection = Campaign::OPERATING_SYSTEM_UNKNOWN;
        }

        // store result to redis to faster resolution on the next attempt
        $this->localCache[$osKey] = $osDetection;
        $this->redis->setex($osKey, self::DEVICE_DETECTION_TTL, $osDetection);

        if ($osDetection === Campaign::OPERATING_SYSTEM_UNKNOWN
            || !in_array($osDetection, $allowedOperatingSystems, true)
        ) {
            return false;
        }

        return true;
    }

    private function resolveCachedValue(string $cacheKey): ?string
    {
        if (isset($this->localCache[$cacheKey])) {
            $cacheValue = $this->localCache[$cacheKey];
        } else {
            $cacheValue = $this->redis->get($cacheKey);
            if ($cacheValue) {
                $this->localCache[$cacheKey] = $cacheValue;
            }
        }
        return $cacheValue;
    }

    private function resolveDevice(string $userAgent): DeviceDetector
    {
        if (!isset($this->deviceDetectorResults[$userAgent])) {
            $this->deviceDetectorResults[$userAgent] = $this->deviceDetector->get($userAgent);
        }
        return $this->deviceDetectorResults[$userAgent];
    }

    public function flushLocalCache(): self
    {
        $this->localCache = [];
        $this->deviceDetectorResults = [];
        return $this;
    }
}
