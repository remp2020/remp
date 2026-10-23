<?php

namespace Remp\CampaignModule\Http\Showtime;

use Remp\CampaignModule\Models\Campaign\CampaignBannerView;
use Remp\CampaignModule\Models\Campaign\CampaignView;

class VariantSelector
{
    /**
     * Selects the variant previously displayed to the user, or a random variant based on variants' proportions
     * if the user hasn't seen any (still available) variant yet.
     *
     * @param ?object $seenCampaigns campaigns seen by the user, as sent in the showtime request
     * @throws VariantSelectionException if no variant can be selected, message says why
     */
    public function selectVariant(CampaignView $campaign, $seenCampaigns): CampaignBannerView
    {
        $variants = [];
        foreach ($campaign->campaignBanners as $campaignBanner) {
            $variants[$campaignBanner->uuid] = $campaignBanner;
        }

        if (!$variants) {
            throw new VariantSelectionException("Campaign not shown because it has no banner set");
        }

        $variantUuid = null;

        // find variant previously displayed to user
        if ($seenCampaigns) {
            if (isset($seenCampaigns->{$campaign->uuid})) {
                $variantUuid = $seenCampaigns->{$campaign->uuid}->variantId ?? null;
            }

            if (isset($seenCampaigns->{$campaign->publicId}->variantId)) {
                foreach ($variants as $variant) {
                    if ($variant->publicId === $seenCampaigns->{$campaign->publicId}->variantId) {
                        $variantUuid = $variant->uuid;
                        break;
                    }
                }
            }
        }

        // unset seen variant if it was deleted
        $seenVariant = $variants[$variantUuid] ?? null;
        if (!$seenVariant) {
            $variantUuid = null;
        }

        // unset seen variant if its proportion is 0%
        if ($seenVariant && $seenVariant->proportion === 0) {
            $variantUuid = null;
        }

        // variant still not set, choose random variant
        if ($variantUuid === null) {
            $randVal = mt_rand(1, 100);
            $currPercent = 0;

            foreach ($variants as $uuid => $variant) {
                $currPercent = $currPercent + $variant->proportion;
                if ($currPercent >= $randVal) {
                    $variantUuid = $uuid;
                    break;
                }
            }
        }

        $variant = $variants[$variantUuid] ?? null;
        if (!$variant) {
            throw new VariantSelectionException("Campaign not executed because there is no CampaignBanner with UUID [{$variantUuid}]");
        }

        return $variant;
    }
}
