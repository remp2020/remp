<?php

namespace Remp\CampaignModule\Tests\Unit;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\Http\Showtime\VariantSelectionException;
use Remp\CampaignModule\Http\Showtime\VariantSelector;
use Remp\CampaignModule\Tests\TestCase;

class VariantSelectorTest extends TestCase
{
    private VariantSelector $variantSelector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->variantSelector = new VariantSelector();
    }

    public function testCampaignWithoutVariants()
    {
        $campaign = $this->campaign([]);

        $this->expectException(VariantSelectionException::class);
        $this->expectExceptionMessage('Campaign not shown because it has no banner set');
        $this->variantSelector->selectVariant($campaign, null);
    }

    public function testRandomVariantForNewUser()
    {
        $campaign = $this->campaign([
            $this->variant('variant-a', 0),
            $this->variant('variant-b', 100),
        ]);

        $result = $this->variantSelector->selectVariant($campaign, null);
        $this->assertSame('variant-b', $result->uuid);
    }

    public function testSeenVariantByCampaignPublicId()
    {
        $campaign = $this->campaign([
            $this->variant('variant-a', 50),
            $this->variant('variant-b', 50),
        ]);

        $seenCampaigns = (object) [
            $campaign->public_id => (object) ['variantId' => 'variant-b-public'],
        ];

        $result = $this->variantSelector->selectVariant($campaign, $seenCampaigns);
        $this->assertSame('variant-b', $result->uuid);
    }

    public function testSeenVariantByCampaignUuid()
    {
        $campaign = $this->campaign([
            $this->variant('variant-a', 50),
            $this->variant('variant-b', 50),
        ]);

        $seenCampaigns = (object) [
            $campaign->uuid => (object) ['variantId' => 'variant-b'],
        ];

        $result = $this->variantSelector->selectVariant($campaign, $seenCampaigns);
        $this->assertSame('variant-b', $result->uuid);
    }

    public function testDeletedSeenVariantIsReselected()
    {
        $campaign = $this->campaign([
            $this->variant('variant-a', 100),
        ]);

        $seenCampaigns = (object) [
            $campaign->public_id => (object) ['variantId' => 'deleted-variant-public'],
        ];

        $result = $this->variantSelector->selectVariant($campaign, $seenCampaigns);
        $this->assertSame('variant-a', $result->uuid);
    }

    public function testSeenVariantWithZeroProportionIsReselected()
    {
        $campaign = $this->campaign([
            $this->variant('variant-a', 0),
            $this->variant('variant-b', 100),
        ]);

        $seenCampaigns = (object) [
            $campaign->public_id => (object) ['variantId' => 'variant-a-public'],
        ];

        $result = $this->variantSelector->selectVariant($campaign, $seenCampaigns);
        $this->assertSame('variant-b', $result->uuid);
    }

    private function campaign(array $variants): Campaign
    {
        $campaign = new Campaign();
        $campaign->uuid = 'campaign-uuid';
        $campaign->public_id = 'campaign-public';
        $campaign->setRelation('campaignBanners', collect($variants));
        return $campaign;
    }

    private function variant(string $uuid, int $proportion): CampaignBanner
    {
        $variant = new CampaignBanner();
        $variant->uuid = $uuid;
        $variant->public_id = "{$uuid}-public";
        $variant->proportion = $proportion;
        return $variant;
    }
}
