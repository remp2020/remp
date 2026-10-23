<?php

namespace Remp\CampaignModule\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Remp\CampaignModule\Http\Showtime\VariantSelectionException;
use Remp\CampaignModule\Http\Showtime\VariantSelector;
use Remp\CampaignModule\Models\Campaign\CampaignBannerView;

class VariantSelectorTest extends TestCase
{
    use CreatesCampaignView;

    private VariantSelector $variantSelector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->variantSelector = new VariantSelector();
    }

    public function testCampaignWithoutVariants()
    {
        $campaign = $this->campaignView([]);

        $this->expectException(VariantSelectionException::class);
        $this->expectExceptionMessage('Campaign not shown because it has no banner set');
        $this->variantSelector->selectVariant($campaign, null);
    }

    public function testRandomVariantForNewUser()
    {
        $campaign = $this->campaignView([
            $this->variant('variant-a', 0),
            $this->variant('variant-b', 100),
        ]);

        $result = $this->variantSelector->selectVariant($campaign, null);
        $this->assertSame('variant-b', $result->uuid);
    }

    public function testSeenVariantByCampaignPublicId()
    {
        $campaign = $this->campaignView([
            $this->variant('variant-a', 50),
            $this->variant('variant-b', 50),
        ]);

        $seenCampaigns = (object) [
            $campaign->publicId => (object) ['variantId' => 'variant-b-public'],
        ];

        $result = $this->variantSelector->selectVariant($campaign, $seenCampaigns);
        $this->assertSame('variant-b', $result->uuid);
    }

    public function testSeenVariantByCampaignUuid()
    {
        $campaign = $this->campaignView([
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
        $campaign = $this->campaignView([
            $this->variant('variant-a', 100),
        ]);

        $seenCampaigns = (object) [
            $campaign->publicId => (object) ['variantId' => 'deleted-variant-public'],
        ];

        $result = $this->variantSelector->selectVariant($campaign, $seenCampaigns);
        $this->assertSame('variant-a', $result->uuid);
    }

    public function testSeenVariantWithZeroProportionIsReselected()
    {
        $campaign = $this->campaignView([
            $this->variant('variant-a', 0),
            $this->variant('variant-b', 100),
        ]);

        $seenCampaigns = (object) [
            $campaign->publicId => (object) ['variantId' => 'variant-a-public'],
        ];

        $result = $this->variantSelector->selectVariant($campaign, $seenCampaigns);
        $this->assertSame('variant-b', $result->uuid);
    }

    private function variant(string $uuid, int $proportion): CampaignBannerView
    {
        return new CampaignBannerView(id: 1, uuid: $uuid, publicId: "{$uuid}-public", proportion: $proportion, bannerId: 1, controlGroup: false);
    }
}
