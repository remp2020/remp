<?php

namespace Remp\CampaignModule\Tests\Unit\Rules;

use Illuminate\Support\Arr;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Remp\CampaignModule\Http\Requests\CampaignRequest;
use Remp\CampaignModule\Rules\ValidIpRange;

class ValidIpRangeTest extends TestCase
{
    #[DataProvider('provideRanges')]
    public function testValidate(mixed $range, array $expectedErrors): void
    {
        $this->assertSame($expectedErrors, $this->validate($range));
    }

    public static function provideRanges(): array
    {
        return [
            'ToGreaterThanFrom_Valid' => [
                'range' => ['ip_from' => '192.168.1.0', 'ip_to' => '192.168.1.255'],
                'expectedErrors' => [],
            ],
            'ToEqualsFrom_Valid' => [
                'range' => ['ip_from' => '192.168.1.1', 'ip_to' => '192.168.1.1'],
                'expectedErrors' => [],
            ],
            'ToLowerThanFrom_Invalid' => [
                'range' => ['ip_from' => '192.168.1.255', 'ip_to' => '192.168.1.0'],
                'expectedErrors' => [
                    'IP "from" address "192.168.1.255" must be lower than or equal to IP "to" address "192.168.1.0".',
                ],
            ],
            'ToNull_Valid' => [
                'range' => ['ip_from' => '192.168.1.1', 'ip_to' => null],
                'expectedErrors' => [],
            ],
            'ToEmpty_Valid' => [
                'range' => ['ip_from' => '192.168.1.1', 'ip_to' => ''],
                'expectedErrors' => [],
            ],
            'ToMissing_Valid' => [
                'range' => ['ip_from' => '192.168.1.1'],
                'expectedErrors' => [],
            ],
            'ToWhitespace_Invalid' => [
                'range' => ['ip_from' => '192.168.1.1', 'ip_to' => ' '],
                'expectedErrors' => ['IP "to" address " " is not a valid IP address.'],
            ],
            'ToNotAnIp_Invalid' => [
                'range' => ['ip_from' => '192.168.1.1', 'ip_to' => 'not-an-ip'],
                'expectedErrors' => ['IP "to" address "not-an-ip" is not a valid IP address.'],
            ],
            'FromNotAnIp_Invalid' => [
                'range' => ['ip_from' => 'not-an-ip', 'ip_to' => '192.168.1.0'],
                'expectedErrors' => ['IP "from" address "not-an-ip" is not a valid IP address.'],
            ],
            'FromWithCidrSuffix_Invalid' => [
                'range' => ['ip_from' => '192.168.1.0/24', 'ip_to' => null],
                'expectedErrors' => ['IP "from" address "192.168.1.0/24" is not a valid IP address.'],
            ],
            'FromNotAString_Invalid' => [
                'range' => ['ip_from' => ['nested'], 'ip_to' => '192.168.1.0'],
                'expectedErrors' => ['IP "from" address is not a valid IP address.'],
            ],
            'FromAndToNotAnIp_BothReported' => [
                'range' => ['ip_from' => 'not-an-ip', 'ip_to' => 'neither'],
                'expectedErrors' => [
                    'IP "from" address "not-an-ip" is not a valid IP address.',
                    'IP "to" address "neither" is not a valid IP address.',
                ],
            ],
            'FromMissing_Required' => [
                'range' => ['ip_to' => '192.168.1.0'],
                'expectedErrors' => ['IP "from" address is required.'],
            ],
            'FromNull_Required' => [
                'range' => ['ip_from' => null, 'ip_to' => '192.168.1.0'],
                'expectedErrors' => ['IP "from" address is required.'],
            ],
            'FromWhitespace_Required' => [
                'range' => ['ip_from' => ' ', 'ip_to' => null],
                'expectedErrors' => ['IP "from" address is required.'],
            ],
            'RangeNotAnArray_Required' => [
                'range' => '192.168.1.1',
                'expectedErrors' => ['IP "from" address is required.'],
            ],
            'Ipv6ToGreaterThanFrom_Valid' => [
                'range' => ['ip_from' => '2001:db8::', 'ip_to' => '2001:db8::ffff'],
                'expectedErrors' => [],
            ],
            'Ipv6ToLowerThanFrom_Invalid' => [
                'range' => ['ip_from' => '2001:db8::ffff', 'ip_to' => '2001:db8::'],
                'expectedErrors' => [
                    'IP "from" address "2001:db8::ffff" must be lower than or equal to IP "to" address "2001:db8::".',
                ],
            ],
            'MixedFamilies_Invalid' => [
                'range' => ['ip_from' => '192.168.1.0', 'ip_to' => '2001:db8::ffff'],
                'expectedErrors' => [
                    'IP "from" address "192.168.1.0" and IP "to" address "2001:db8::ffff" must be both IPv4 or both IPv6.',
                ],
            ],
        ];
    }

    #[DataProvider('provideRequestData')]
    public function testCampaignRequestRules(array $data, array $expectedErrors): void
    {
        $rules = Arr::only((new CampaignRequest())->rules(), ['ip_ranges', 'ip_ranges.*']);
        $validator = (new Factory(new Translator(new ArrayLoader(), 'en')))->make($data, $rules);

        $this->assertSame($expectedErrors, $validator->errors()->toArray());
    }

    public static function provideRequestData(): array
    {
        return [
            'RangesMissing_Valid' => [
                'data' => [],
                'expectedErrors' => [],
            ],
            'RangesEmpty_Valid' => [
                'data' => ['ip_ranges' => []],
                'expectedErrors' => [],
            ],
            'EmptyRange_Required' => [
                'data' => ['ip_ranges' => [[]]],
                'expectedErrors' => ['ip_ranges.0' => ['IP "from" address is required.']],
            ],
            'NullRange_Required' => [
                'data' => ['ip_ranges' => [null]],
                'expectedErrors' => ['ip_ranges.0' => ['IP "from" address is required.']],
            ],
            'EmptyStringRange_Required' => [
                'data' => ['ip_ranges' => ['']],
                'expectedErrors' => ['ip_ranges.0' => ['IP "from" address is required.']],
            ],
            'SecondRangeInvalid_ReportedUnderItsIndex' => [
                'data' => [
                    'ip_ranges' => [
                        ['ip_from' => '192.168.1.1', 'ip_to' => null],
                        ['ip_from' => 'not-an-ip', 'ip_to' => null],
                    ],
                ],
                'expectedErrors' => ['ip_ranges.1' => ['IP "from" address "not-an-ip" is not a valid IP address.']],
            ],
        ];
    }

    private function validate(mixed $value): array
    {
        $errors = [];
        $fail = function (string $message) use (&$errors) {
            $errors[] = $message;
        };
        (new ValidIpRange())->validate('ip_ranges.0', $value, $fail);

        return $errors;
    }
}
