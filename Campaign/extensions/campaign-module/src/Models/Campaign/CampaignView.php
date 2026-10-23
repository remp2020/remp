<?php

namespace Remp\CampaignModule\Models\Campaign;

use Illuminate\Database\Eloquent\Collection;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\Models\Schedule\ScheduleView;
use Remp\CampaignModule\Models\Targeting\TargetingMatchEnum;

/**
 * Campaign as it's cached, usable without the Eloquent models.
 */
readonly class CampaignView
{
    /**
     * @param string $updatedAt in the Eloquent model's date format
     * @param ScheduleView[] $schedules
     * @param CampaignBannerView[] $campaignBanners
     * @param CampaignSegmentView[] $segments
     * @param CampaignIpRangeView[] $ipRanges
     * @param CountryView[] $countries
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $publicId,
        public string $updatedAt,
        public ?bool $signedIn,
        public bool $oncePerSession,
        public ?bool $usingAdblock,
        public string $urlFilter,
        public array $urlPatterns,
        public string $sourceFilter,
        public array $sourcePatterns,
        public array $devices,
        public array $operatingSystems,
        public array $languages,
        public array $pageviewAttributes,
        public ?array $pageviewRules,
        public array $schedules,
        public array $campaignBanners,
        public array $segments,
        public array $ipRanges,
        public array $countries,
    ) {
    }

    public static function fromModel(Campaign $campaign): self
    {
        $campaign->loadMissing(['schedules', 'campaignBanners', 'segments', 'ipRanges', 'countries']);

        $schedules = [];
        foreach ($campaign->schedules as $schedule) {
            $schedules[] = ScheduleView::fromModel($schedule);
        }
        $campaignBanners = [];
        foreach ($campaign->campaignBanners as $campaignBanner) {
            $campaignBanners[] = CampaignBannerView::fromModel($campaignBanner);
        }
        $segments = [];
        foreach ($campaign->segments as $segment) {
            $segments[] = CampaignSegmentView::fromModel($segment);
        }
        $ipRanges = [];
        foreach ($campaign->ipRanges as $ipRange) {
            $ipRanges[] = CampaignIpRangeView::fromModel($ipRange);
        }
        $countries = [];
        foreach ($campaign->countries as $country) {
            $countries[] = CountryView::fromModel($country);
        }

        return new self(
            id: $campaign->id,
            uuid: $campaign->uuid,
            publicId: $campaign->public_id,
            updatedAt: $campaign->fromDateTime($campaign->updated_at),
            signedIn: $campaign->signed_in,
            oncePerSession: $campaign->once_per_session,
            usingAdblock: $campaign->using_adblock,
            urlFilter: $campaign->url_filter,
            urlPatterns: $campaign->url_patterns ?? [],
            sourceFilter: $campaign->source_filter,
            sourcePatterns: $campaign->source_patterns ?? [],
            devices: $campaign->devices,
            operatingSystems: $campaign->operating_systems ?? [],
            languages: $campaign->languages ?? [],
            pageviewAttributes: $campaign->pageview_attributes ?? [],
            pageviewRules: $campaign->pageview_rules,
            schedules: $schedules,
            campaignBanners: $campaignBanners,
            segments: $segments,
            ipRanges: $ipRanges,
            countries: $countries,
        );
    }

    public static function fromCache(array $campaign): self
    {
        $schedules = [];
        foreach ($campaign['schedules'] as $schedule) {
            $schedules[] = ScheduleView::fromCache($schedule);
        }
        $campaignBanners = [];
        foreach ($campaign['campaign_banners'] as $campaignBanner) {
            $campaignBanners[] = CampaignBannerView::fromCache($campaignBanner);
        }
        $segments = [];
        foreach ($campaign['segments'] as $segment) {
            $segments[] = CampaignSegmentView::fromCache($segment);
        }
        $ipRanges = [];
        foreach ($campaign['ip_ranges'] as $ipRange) {
            $ipRanges[] = CampaignIpRangeView::fromCache($ipRange);
        }
        $countries = [];
        foreach ($campaign['countries'] as $country) {
            $countries[] = CountryView::fromCache($country);
        }

        return new self(
            id: $campaign['id'],
            uuid: $campaign['uuid'],
            publicId: $campaign['public_id'],
            updatedAt: $campaign['updated_at'],
            signedIn: $campaign['signed_in'],
            oncePerSession: $campaign['once_per_session'],
            usingAdblock: $campaign['using_adblock'],
            urlFilter: $campaign['url_filter'],
            urlPatterns: $campaign['url_patterns'],
            sourceFilter: $campaign['source_filter'],
            sourcePatterns: $campaign['source_patterns'],
            devices: $campaign['devices'],
            operatingSystems: $campaign['operating_systems'],
            languages: $campaign['languages'],
            pageviewAttributes: $campaign['pageview_attributes'],
            pageviewRules: $campaign['pageview_rules'],
            schedules: $schedules,
            campaignBanners: $campaignBanners,
            segments: $segments,
            ipRanges: $ipRanges,
            countries: $countries,
        );
    }

    public function toCache(): array
    {
        $schedules = [];
        foreach ($this->schedules as $schedule) {
            $schedules[] = $schedule->toCache();
        }
        $campaignBanners = [];
        foreach ($this->campaignBanners as $campaignBanner) {
            $campaignBanners[] = $campaignBanner->toCache();
        }
        $segments = [];
        foreach ($this->segments as $segment) {
            $segments[] = $segment->toCache();
        }
        $ipRanges = [];
        foreach ($this->ipRanges as $ipRange) {
            $ipRanges[] = $ipRange->toCache();
        }
        $countries = [];
        foreach ($this->countries as $country) {
            $countries[] = $country->toCache();
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'public_id' => $this->publicId,
            'updated_at' => $this->updatedAt,
            'signed_in' => $this->signedIn,
            'once_per_session' => $this->oncePerSession,
            'using_adblock' => $this->usingAdblock,
            'url_filter' => $this->urlFilter,
            'url_patterns' => $this->urlPatterns,
            'source_filter' => $this->sourceFilter,
            'source_patterns' => $this->sourcePatterns,
            'devices' => $this->devices,
            'operating_systems' => $this->operatingSystems,
            'languages' => $this->languages,
            'pageview_attributes' => $this->pageviewAttributes,
            'pageview_rules' => $this->pageviewRules,
            'schedules' => $schedules,
            'campaign_banners' => $campaignBanners,
            'segments' => $segments,
            'ip_ranges' => $ipRanges,
            'countries' => $countries,
        ];
    }

    /**
     * Eloquent model with the attributes needed for displaying the campaign (prioritization and rendering).
     */
    public function toModel(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setRawAttributes([
            'id' => $this->id,
            'uuid' => $this->uuid,
            'public_id' => $this->publicId,
            'updated_at' => $this->updatedAt,
        ]);
        $campaign->exists = true;

        $campaignBanners = new Collection();
        foreach ($this->campaignBanners as $campaignBanner) {
            $campaignBanners->push($campaignBanner->toModel($this->id));
        }
        $campaign->setRelation('campaignBanners', $campaignBanners);

        return $campaign;
    }

    public function isRunning(): bool
    {
        foreach ($this->schedules as $schedule) {
            if ($schedule->isRunning()) {
                return true;
            }
        }
        return false;
    }

    public function matchIpRanges(?string $ip): TargetingMatchEnum
    {
        return $this->matchTargeting($this->ipRanges, $ip);
    }

    public function matchCountry(string $countryCode): TargetingMatchEnum
    {
        return $this->matchTargeting($this->countries, $countryCode);
    }

    /**
     * Blacklisted items take precedence; if there are whitelisted items, one of them has to match.
     *
     * @param CampaignIpRangeView[]|CountryView[] $items
     */
    private function matchTargeting(array $items, ?string $value): TargetingMatchEnum
    {
        foreach ($items as $item) {
            if ($item->blacklisted && $value !== null && $item->matches($value)) {
                return TargetingMatchEnum::Blacklisted;
            }
        }

        $hasWhitelist = false;
        foreach ($items as $item) {
            if ($item->blacklisted) {
                continue;
            }
            if ($value !== null && $item->matches($value)) {
                return TargetingMatchEnum::Allowed;
            }
            $hasWhitelist = true;
        }

        return $hasWhitelist ? TargetingMatchEnum::NotWhitelisted : TargetingMatchEnum::Allowed;
    }
}
