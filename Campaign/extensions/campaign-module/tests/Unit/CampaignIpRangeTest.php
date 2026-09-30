<?php

namespace Remp\CampaignModule\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Remp\CampaignModule\CampaignIpRange;

class CampaignIpRangeTest extends TestCase
{
    #[DataProvider('provideIps')]
    public function testContainsIp(string $visitorIp, string $ipFrom, ?string $ipTo, bool $expected): void
    {
        $range = new CampaignIpRange(['ip_from' => $ipFrom, 'ip_to' => $ipTo]);

        $this->assertSame($expected, $range->containsIp($visitorIp));
    }

    public static function provideIps(): array
    {
        return [
            'Ipv4Single_Match' => [
                'visitorIp' => '192.168.1.50',
                'ipFrom' => '192.168.1.50',
                'ipTo' => null,
                'expected' => true,
            ],
            'Ipv4Single_NoMatch' => [
                'visitorIp' => '192.168.1.51',
                'ipFrom' => '192.168.1.50',
                'ipTo' => null,
                'expected' => false,
            ],
            'Ipv4Range_Inside' => [
                'visitorIp' => '192.168.1.50',
                'ipFrom' => '192.168.1.0',
                'ipTo' => '192.168.1.255',
                'expected' => true,
            ],
            'Ipv4Range_LowerBoundary' => [
                'visitorIp' => '192.168.1.0',
                'ipFrom' => '192.168.1.0',
                'ipTo' => '192.168.1.255',
                'expected' => true,
            ],
            'Ipv4Range_UpperBoundary' => [
                'visitorIp' => '192.168.1.255',
                'ipFrom' => '192.168.1.0',
                'ipTo' => '192.168.1.255',
                'expected' => true,
            ],
            'Ipv4Range_Below' => [
                'visitorIp' => '192.168.0.255',
                'ipFrom' => '192.168.1.0',
                'ipTo' => '192.168.1.255',
                'expected' => false,
            ],
            'Ipv4Range_Above' => [
                'visitorIp' => '192.168.2.0',
                'ipFrom' => '192.168.1.0',
                'ipTo' => '192.168.1.255',
                'expected' => false,
            ],
            // packed bytes are "1e10" and "9999": as numbers 1e10 > 9999, as strings it is in range
            'Ipv4RangeNumericLookingBytes_Inside' => [
                'visitorIp' => '49.101.49.48',
                'ipFrom' => '49.101.49.48',
                'ipTo' => '57.57.57.57',
                'expected' => true,
            ],
            'Ipv6Single_Match' => [
                'visitorIp' => '2001:db8::10',
                'ipFrom' => '2001:db8::10',
                'ipTo' => null,
                'expected' => true,
            ],
            'Ipv6Single_NoMatch' => [
                'visitorIp' => '2001:db8::11',
                'ipFrom' => '2001:db8::10',
                'ipTo' => null,
                'expected' => false,
            ],
            'Ipv6Range_Inside' => [
                'visitorIp' => '2001:db8::10',
                'ipFrom' => '2001:db8::',
                'ipTo' => '2001:db8::ffff',
                'expected' => true,
            ],
            'Ipv6Range_Above' => [
                'visitorIp' => '2001:db8::1:0',
                'ipFrom' => '2001:db8::',
                'ipTo' => '2001:db8::ffff',
                'expected' => false,
            ],
            'Ipv6VisitorInIpv4Range_NoMatch' => [
                'visitorIp' => '2001:db8::10',
                'ipFrom' => '192.168.1.0',
                'ipTo' => '192.168.1.255',
                'expected' => false,
            ],
            'Ipv4VisitorInIpv6Range_NoMatch' => [
                'visitorIp' => '192.168.1.50',
                'ipFrom' => '2001:db8::',
                'ipTo' => '2001:db8::ffff',
                'expected' => false,
            ],
            'InvalidVisitorIp_NoMatch' => [
                'visitorIp' => 'not-an-ip',
                'ipFrom' => '192.168.1.0',
                'ipTo' => '192.168.1.255',
                'expected' => false,
            ],
            'InvalidStoredFrom_NoMatch' => [
                'visitorIp' => '192.168.1.50',
                'ipFrom' => 'not-an-ip',
                'ipTo' => '192.168.1.255',
                'expected' => false,
            ],
            'InvalidStoredTo_NoMatch' => [
                'visitorIp' => '192.168.1.50',
                'ipFrom' => '192.168.1.0',
                'ipTo' => 'not-an-ip',
                'expected' => false,
            ],
        ];
    }

    public function testPackIp(): void
    {
        $this->assertSame(4, strlen(CampaignIpRange::packIp('192.168.1.1')));
        $this->assertSame(16, strlen(CampaignIpRange::packIp('2001:db8::1')));
        $this->assertNull(CampaignIpRange::packIp('not-an-ip'));
        $this->assertNull(CampaignIpRange::packIp(''));
    }
}
