<?php

namespace Remp\CampaignModule\Http\Showtime;

use Illuminate\Support\Collection;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;

class VariantSelector
{
    /**
     * Selects the variant previously displayed to the user, or a random variant based on variants' proportions
     * if the user hasn't seen any (still available) variant yet.
     *
     * @param ?object $seenCampaigns campaigns seen by the user, as sent in the showtime request
     * @throws VariantSelectionException if no variant can be selected, message says why
     */
    public function selectVariant(Campaign $campaign, $seenCampaigns): CampaignBanner
    {
        /** @var Collection $campaignBanners */
        $campaignBanners = $campaign->campaignBanners->keyBy('uuid');

        if ($campaignBanners->count() == 0) {
            throw new VariantSelectionException("Campaign not shown because it has no banner set");
        }

        $variantUuid = null;

        // find variant previously displayed to user
        if ($seenCampaigns) {
            if (isset($seenCampaigns->{$campaign->uuid})) {
                $variantUuid = $seenCampaigns->{$campaign->uuid}->variantId ?? null;
            }

            if (isset($seenCampaigns->{$campaign->public_id}->variantId)) {
                foreach ($campaign->campaignBanners as $campaignBanner) {
                    if ($campaignBanner->public_id === $seenCampaigns->{$campaign->public_id}->variantId) {
                        $variantUuid = $campaignBanner->uuid ?? null;
                        break;
                    }
                }
            }
        }

        // unset seen variant if it was deleted
        /** @var CampaignBanner $seenVariant */
        $seenVariant = $campaignBanners->get($variantUuid);
        if (!$seenVariant) {
            $variantUuid = null;
        }

        // unset seen variant if its proportion is 0%
        if ($seenVariant && $seenVariant->proportion === 0) {
            $variantUuid = null;
        }

        // variant still not set, choose random variant
        if ($variantUuid === null) {
            $variantsMapping = $campaign->getVariantsProportionMapping();

            $randVal = mt_rand(1, 100);
            $currPercent = 0;

            foreach ($variantsMapping as $uuid => $proportion) {
                $currPercent = $currPercent + $proportion;
                if ($currPercent >= $randVal) {
                    $variantUuid = $uuid;
                    break;
                }
            }
        }

        /** @var CampaignBanner $variant */
        $variant = $campaignBanners->get($variantUuid);
        if (!$variant) {
            throw new VariantSelectionException("Campaign not executed because there is no CampaignBanner with UUID [{$variantUuid}]");
        }

        return $variant;
    }
}
