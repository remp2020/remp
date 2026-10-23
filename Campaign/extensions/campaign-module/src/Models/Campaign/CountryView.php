<?php

namespace Remp\CampaignModule\Models\Campaign;

use Remp\CampaignModule\Country;

readonly class CountryView
{
    public function __construct(
        public string $isoCode,
        public bool $blacklisted,
    ) {
    }

    public static function fromModel(Country $country): self
    {
        return new self(
            isoCode: $country->iso_code,
            blacklisted: $country->isBlacklisted(),
        );
    }

    public static function fromCache(array $country): self
    {
        return new self(
            isoCode: $country['iso_code'],
            blacklisted: $country['blacklisted'],
        );
    }

    public function toCache(): array
    {
        return [
            'iso_code' => $this->isoCode,
            'blacklisted' => $this->blacklisted,
        ];
    }

    public function matches(string $countryCode): bool
    {
        return $this->isoCode === $countryCode;
    }
}
