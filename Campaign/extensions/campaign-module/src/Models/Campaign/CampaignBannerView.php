<?php

namespace Remp\CampaignModule\Models\Campaign;

use Remp\CampaignModule\CampaignBanner;

readonly class CampaignBannerView
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $publicId,
        public int $proportion,
        public ?int $bannerId,
        public bool $controlGroup,
    ) {
    }

    public static function fromModel(CampaignBanner $campaignBanner): self
    {
        return new self(
            id: $campaignBanner->id,
            uuid: $campaignBanner->uuid,
            publicId: $campaignBanner->public_id,
            proportion: $campaignBanner->proportion,
            bannerId: $campaignBanner->banner_id,
            controlGroup: (bool) $campaignBanner->control_group,
        );
    }

    public static function fromCache(array $campaignBanner): self
    {
        return new self(
            id: $campaignBanner['id'],
            uuid: $campaignBanner['uuid'],
            publicId: $campaignBanner['public_id'],
            proportion: $campaignBanner['proportion'],
            bannerId: $campaignBanner['banner_id'],
            controlGroup: $campaignBanner['control_group'],
        );
    }

    public function toCache(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'public_id' => $this->publicId,
            'proportion' => $this->proportion,
            'banner_id' => $this->bannerId,
            'control_group' => $this->controlGroup,
        ];
    }

    public function toModel(int $campaignId): CampaignBanner
    {
        $campaignBanner = new CampaignBanner();
        $campaignBanner->setRawAttributes([
            'id' => $this->id,
            'campaign_id' => $campaignId,
            'uuid' => $this->uuid,
            'public_id' => $this->publicId,
            'banner_id' => $this->bannerId,
            'control_group' => (int) $this->controlGroup,
        ]);
        $campaignBanner->exists = true;
        return $campaignBanner;
    }
}
