<?php

namespace Remp\CampaignModule\Tests\Unit;

use Predis\ClientInterface;
use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\Crm\Segment as CrmSegment;
use Remp\CampaignModule\Contracts\Crm\SegmentFactory as CrmSegmentFactory;
use Remp\CampaignModule\Contracts\Remp\SegmentFactory as RempSegmentFactory;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Contracts\SegmentContract;
use Remp\CampaignModule\Contracts\SegmentContractFactory;
use Remp\CampaignModule\Contracts\SegmentException;
use Remp\CampaignModule\Tests\TestCase;
use GuzzleHttp\Client;

class SegmentAggregatorTest extends TestCase
{
    private const REDIS_KEY = 'segment_aggregator_config';

    private ClientInterface $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = resolve(ClientInterface::class);
    }

    protected function tearDown(): void
    {
        $this->redis->del([self::REDIS_KEY, 'crm_segment|test_segment', 'crm_segment|other_segment']);
        parent::tearDown();
    }

    public function testFromFactoriesReadsConfig()
    {
        config([
            'services.remp.beam.segments_addr' => 'http://beam-segments.example.com',
            'services.remp.beam.segments_timeout' => 7,
            'services.remp.crm_segment.base_url' => 'http://crm.example.com',
            'services.remp.crm_segment.token' => 'secret',
        ]);

        SegmentAggregator::fromFactories([new RempSegmentFactory(), new CrmSegmentFactory()], $this->redis)
            ->cacheConfig();

        $this->assertSame([
            RempSegmentFactory::class => ['base_url' => 'http://beam-segments.example.com', 'timeout' => 7],
            CrmSegmentFactory::class => ['base_url' => 'http://crm.example.com', 'token' => 'secret'],
        ], json_decode($this->redis->get(self::REDIS_KEY), true));
    }

    public function testFromCachedConfig()
    {
        $this->redis->set(self::REDIS_KEY, json_encode([
            CrmSegmentFactory::class => ['base_url' => 'http://crm.example.com', 'token' => 'secret'],
        ]));

        $aggregator = SegmentAggregator::fromCachedConfig($this->redis);

        // created CRM contract uses the provided Redis client for its cached segments
        $campaignSegment = new CampaignSegment();
        $campaignSegment->provider = CrmSegment::PROVIDER_ALIAS;
        $campaignSegment->code = 'test_segment';
        $this->redis->sadd(SegmentAggregator::cacheKey($campaignSegment), ['123']);

        $this->assertTrue($aggregator->checkUser($campaignSegment, '123'));
        $this->assertFalse($aggregator->checkUser($campaignSegment, '456'));
    }

    public function testFromCachedConfigMissing()
    {
        $this->redis->del([self::REDIS_KEY]);

        $this->assertNull(SegmentAggregator::fromCachedConfig($this->redis));
    }

    public function testContractsCreatedOnFirstUse()
    {
        CountingSegmentFactory::$created = 0;
        $aggregator = new SegmentAggregator([CountingSegmentFactory::class => []], $this->redis);
        $this->assertSame(0, CountingSegmentFactory::$created);

        $campaignSegment = new CampaignSegment();
        $campaignSegment->provider = CrmSegment::PROVIDER_ALIAS;
        $campaignSegment->code = 'test_segment';
        $aggregator->checkUser($campaignSegment, '123');
        $aggregator->checkUser($campaignSegment, '456');
        $this->assertSame(1, CountingSegmentFactory::$created);
    }

    public function testBatchCheckUserInCacheChecksOnlyCachedSegmentsOfBatchProviders()
    {
        $aggregator = new SegmentAggregator([
            CrmSegmentFactory::class => ['base_url' => 'http://crm.example.com', 'token' => 'secret'],
            RempSegmentFactory::class => ['base_url' => 'http://beam-segments.example.com', 'timeout' => 1],
        ], $this->redis);

        $crmSegment = new CampaignSegment();
        $crmSegment->provider = CrmSegment::PROVIDER_ALIAS;
        $crmSegment->code = 'test_segment';
        $this->redis->sadd(SegmentAggregator::cacheKey($crmSegment), ['123']);

        $otherCrmSegment = new CampaignSegment();
        $otherCrmSegment->provider = CrmSegment::PROVIDER_ALIAS;
        $otherCrmSegment->code = 'other_segment';

        $beamSegment = new CampaignSegment();
        $beamSegment->provider = 'remp_segment';
        $beamSegment->code = 'beam_segment';

        // Beam segments are not cached (checking them means HTTP request for each of them), they're left out
        $this->assertSame([
            'crm_segment|test_segment' => true,
            'crm_segment|other_segment' => false,
        ], $aggregator->batchCheckUserInCache([$crmSegment, $beamSegment, $otherCrmSegment], '123'));

        $this->assertSame([], $aggregator->batchCheckUserInCache([], '123'));
    }

    public function testUnknownFactoryClass()
    {
        $this->expectException(SegmentException::class);

        new SegmentAggregator([\stdClass::class => []], $this->redis);
    }
}

class CountingSegmentFactory implements SegmentContractFactory
{
    public static int $created = 0;

    public function config(): array
    {
        return [];
    }

    public function create(array $config, ClientInterface|\Redis $redis): SegmentContract
    {
        self::$created++;
        return new CrmSegment(new Client(), $redis);
    }
}