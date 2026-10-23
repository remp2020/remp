<?php

namespace Remp\CampaignModule\Models\Campaign;

use Remp\CampaignModule\CampaignSegment;

readonly class CampaignSegmentView
{
    public function __construct(
        public string $provider,
        public string $code,
        public bool $inclusive,
    ) {
    }

    public static function fromModel(CampaignSegment $campaignSegment): self
    {
        return new self(
            provider: $campaignSegment->provider,
            code: $campaignSegment->code,
            inclusive: (bool) $campaignSegment->inclusive,
        );
    }

    public static function fromCache(array $campaignSegment): self
    {
        return new self(
            provider: $campaignSegment['provider'],
            code: $campaignSegment['code'],
            inclusive: $campaignSegment['inclusive'],
        );
    }

    public function toCache(): array
    {
        return [
            'provider' => $this->provider,
            'code' => $this->code,
            'inclusive' => $this->inclusive,
        ];
    }
}
