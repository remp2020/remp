<?php

namespace Remp\CampaignModule\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Remp\CampaignModule\Models\Campaign\CountryView;
use Remp\CampaignModule\Models\Targeting\TargetingMatchEnum;

class CampaignViewTest extends TestCase
{
    use CreatesCampaignView;

    #[DataProvider('provideCountries')]
    public function testMatchCountry(array $countries, TargetingMatchEnum $expected): void
    {
        $this->assertSame($expected, $this->campaignView(countries: $countries)->matchCountry('SK'));
    }

    public static function provideCountries(): array
    {
        $country = fn (string $isoCode, bool $blacklisted) => new CountryView(isoCode: $isoCode, blacklisted: $blacklisted);

        return [
            'NoCountries' => [[], TargetingMatchEnum::Allowed],
            'Whitelisted' => [[$country('SK', false), $country('CZ', false)], TargetingMatchEnum::Allowed],
            'NotWhitelisted' => [[$country('CZ', false)], TargetingMatchEnum::NotWhitelisted],
            'Blacklisted' => [[$country('SK', true)], TargetingMatchEnum::Blacklisted],
            'OtherBlacklisted' => [[$country('CZ', true)], TargetingMatchEnum::Allowed],
            'BlacklistWinsOverWhitelist' => [[$country('SK', false), $country('SK', true)], TargetingMatchEnum::Blacklisted],
        ];
    }
}
