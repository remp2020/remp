<?php

namespace Remp\BeamModule\Tests\Feature;

use Remp\BeamModule\Model\Account;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Console\Commands\ProcessConversionSources;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\ConversionCommerceEvent;
use Remp\BeamModule\Model\ConversionSource;
use Remp\BeamModule\Model\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Remp\Journal\Journal;
use Remp\Journal\JournalContract;
use Remp\BeamModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ProcessConversionSourcesTest extends TestCase
{
    use RefreshDatabase;

    /** @var Property */
    private $property;

    /** @var JournalContract */
    private $journal;

    protected function setUp(): void
    {
        parent::setUp();

        Article::unsetEventDispatcher();

        $account = Account::factory()->create();
        $this->property = Property::factory()->create(['account_id' => $account->id]);

        // Mock Journal data
        $this->journal = Mockery::mock(Journal::class);

        // Bypass RempJournalServiceProvider binding
        $this->app->instance('mockJournal', $this->journal);
        $this->app->when(ProcessConversionSources::class)
            ->needs(JournalContract::class)
            ->give('mockJournal');
    }

    public function testSuccessMultiplePageviews()
    {
        $referalArticle = Article::factory()->create([
            'external_id' => 9,
            'property_uuid' => $this->property->uuid,
        ]);
        $conversionArticle = Article::factory()->create([
            'external_id' => 'article1',
            'property_uuid' => $this->property->uuid,
        ]);
        $conversion = Conversion::factory()->create([
            'user_id' => 26,
            'transaction_id' => '1234567890',
            'article_id' => $conversionArticle->id,
            'events_aggregated' => true,
        ]);
        ConversionCommerceEvent::factory()->create([
            'conversion_id' => $conversion,
            'step' => 'payment',
            'time' => Carbon::createFromTimeString('2020-08-06T14:36:47Z'),
        ]);

        $this->journal->shouldReceive('addFilter')
            ->withArgs([$conversion->transaction_id]);
        $this->journal->shouldReceive('list')
            ->andReturn(
                $this->loadJson('step_payment_1234567890.json'), // to get browser ID
                $this->loadJson('testSuccessMultiplePageviews_pageviews.json'), // to get pageviews
            );

        $this->artisan(ProcessConversionSources::COMMAND, ['--conversion_id' => $conversion->id]);

        //retrieve processed conversion sources
        $conversionSources = ConversionSource::where(['conversion_id' => $conversion->id])->get();
        $firstConversionSource = $conversionSources->where('type', ConversionSource::TYPE_SESSION_FIRST)->first();
        $lastConversionSource = $conversionSources->where('type', ConversionSource::TYPE_SESSION_LAST)->first();

        $this->assertEquals(2, $conversionSources->count());
        $this->assertEquals('search', $firstConversionSource->referer_medium);
        $this->assertEquals('internal', $lastConversionSource->referer_medium);
        $this->assertNull($firstConversionSource->referer_host_with_path);
        $this->assertEquals('http://localhost:63342/remp-sample-blog/index.html', $lastConversionSource->referer_host_with_path);
        $this->assertEquals($referalArticle->id, $lastConversionSource->article_id);

        $conversion->refresh();
        $this->assertTrue($conversion->source_processed);
    }

    public function testSuccessfulSinglePageview()
    {
        $conversionArticle = Article::factory()->create([
            'external_id' => 'article1',
            'property_uuid' => $this->property->uuid,
        ]);
        $conversion = Conversion::factory()->create([
            'user_id' => 26,
            'article_id' => $conversionArticle->id,
            'transaction_id' => '1234567890',
            'events_aggregated' => true,
        ]);
        ConversionCommerceEvent::factory()->create([
            'conversion_id' => $conversion,
            'step' => 'payment',
            'time' => Carbon::createFromTimeString('2020-08-06T14:36:47Z'),
        ]);

        $this->journal->shouldReceive('addFilter')
            ->withArgs([$conversion->transaction_id]);
        $this->journal->shouldReceive('list')
            ->andReturn(
                $this->loadJson('step_payment_1234567890.json'), // to get browser ID
                $this->loadJson('testSuccessSinglePageview_pageviews.json'), // to get pageviews
            );

        $this->artisan(ProcessConversionSources::COMMAND, ['--conversion_id' => $conversion->id]);

        //retrieve processed conversion sources
        $conversionSources = ConversionSource::where(['conversion_id' => $conversion->id])->get();
        $firstConversionSource = $conversionSources->where('type', ConversionSource::TYPE_SESSION_FIRST)->first();
        $lastConversionSource = $conversionSources->where('type', ConversionSource::TYPE_SESSION_LAST)->first();

        $this->assertEquals(2, $conversionSources->count());
        $this->assertEquals('social', $firstConversionSource->referer_medium);
        $this->assertEquals('social', $lastConversionSource->referer_medium);

        $conversion->refresh();
        $this->assertTrue($conversion->source_processed);
    }

    public function testNoPageview()
    {
        $conversionArticle = Article::factory()->create([
            'external_id' => 'article1',
            'property_uuid' => $this->property->uuid,
        ]);
        $conversion = Conversion::factory()->create([
            'user_id' => 26,
            'article_id' => $conversionArticle->id,
            'transaction_id' => '1234567890',
            'events_aggregated' => true,
        ]);
        ConversionCommerceEvent::factory()->create([
            'conversion_id' => $conversion,
            'step' => 'payment',
            'time' => Carbon::createFromTimeString('2020-08-06T14:36:47Z'),
        ]);

        $this->journal->shouldReceive('addFilter')
            ->withArgs([$conversion->transaction_id]);
        $this->journal->shouldReceive('list')
            ->andReturn(
                $this->loadJson('step_payment_1234567890.json'), // to get browser ID
                $this->loadJson('testNoPageview_pageviews.json'), // to get pageviews
            );

        $this->artisan(ProcessConversionSources::COMMAND, ['--conversion_id' => $conversion->id]);

        //retrieve processed conversion sources
        $conversionSources = ConversionSource::where(['conversion_id' => $conversion->id])->get();
        $this->assertEquals(0, $conversionSources->count());

        $conversion->refresh();
        $this->assertTrue($conversion->source_processed);
    }

    public function testEventsNotAggregatedYet()
    {
        $conversionArticle = Article::factory()->create([
            'external_id' => 'article1',
            'property_uuid' => $this->property->uuid,
        ]);
        $conversion = Conversion::factory()->create([
            'user_id' => 26,
            'article_id' => $conversionArticle->id,
            'transaction_id' => '1234567890',
            'events_aggregated' => false,
        ]);

        $this->journal->shouldNotReceive('list');

        $this->artisan(ProcessConversionSources::COMMAND, ['--conversion_id' => $conversion->id]);

        $conversion->refresh();
        $this->assertFalse($conversion->source_processed);
    }

    public function testMissingCommercePaymentEvent()
    {
        $conversionArticle = Article::factory()->create([
            'external_id' => 'article1',
            'property_uuid' => $this->property->uuid,
        ]);
        $conversion = Conversion::factory()->create([
            'user_id' => 26,
            'article_id' => $conversionArticle->id,
            'transaction_id' => '1234567890',
            'events_aggregated' => true,
        ]);
        ConversionCommerceEvent::factory()->create([
            'conversion_id' => $conversion,
            'step' => 'payment',
            'time' => Carbon::createFromTimeString('2020-08-06T14:36:47Z'),
        ]);

        // return empty commerce events list
        $this->journal->shouldReceive('list')->andReturn([]);
        Log::shouldReceive('warning');

        $this->artisan(ProcessConversionSources::COMMAND, ['--conversion_id' => $conversion->id]);

        $conversion->refresh();
        $this->assertTrue($conversion->source_processed);
    }

    public function testMissingBrowserId()
    {
        $conversionArticle = Article::factory()->create([
            'external_id' => 'article1',
            'property_uuid' => $this->property->uuid,
        ]);
        $conversion = Conversion::factory()->create([
            'user_id' => 26,
            'article_id' => $conversionArticle->id,
            'transaction_id' => '1234567890',
            'events_aggregated' => true,
        ]);
        ConversionCommerceEvent::factory()->create([
            'conversion_id' => $conversion,
            'step' => 'payment',
            'time' => Carbon::createFromTimeString('2020-08-06T14:36:47Z'),
        ]);

        // return empty commerce events list
        $this->journal->shouldReceive('list')
            ->andReturn($this->loadJson('step_payment_1234567890_missing_browser_id.json'));
        Log::shouldReceive('warning');

        $this->artisan(ProcessConversionSources::COMMAND, ['--conversion_id' => $conversion->id]);

        $conversion->refresh();
        $this->assertTrue($conversion->source_processed);
    }

    private function loadJson($file)
    {
        return json_decode(
            file_get_contents(__DIR__ . '/ProcessConversionSourcesTest/' . $file),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
