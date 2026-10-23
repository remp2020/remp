<?php

namespace Remp\CampaignModule\Tests\Unit;

use Remp\CampaignModule\Models\Campaign\CampaignView;

trait CreatesCampaignView
{
    private function campaignView(array $campaignBanners = [], array $countries = []): CampaignView
    {
        return new CampaignView(
            id: 1,
            uuid: 'campaign-uuid',
            publicId: 'campaign-public',
            updatedAt: '2026-01-01 00:00:00',
            signedIn: null,
            oncePerSession: false,
            usingAdblock: null,
            urlFilter: 'everywhere',
            urlPatterns: [],
            sourceFilter: 'everywhere',
            sourcePatterns: [],
            devices: [],
            operatingSystems: [],
            languages: [],
            pageviewAttributes: [],
            pageviewRules: null,
            schedules: [],
            campaignBanners: $campaignBanners,
            segments: [],
            ipRanges: [],
            countries: $countries,
        );
    }
}
