<?php

namespace Remp\BeamModule\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\BeamModule\Console\Commands\ComputeAuthorsSegments;
use Remp\BeamModule\Database\Seeders\ConfigSeeder;
use Remp\BeamModule\Database\Seeders\SegmentGroupSeeder;
use Remp\BeamModule\Model\Account;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\ArticleAggregatedView;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentBrowser;
use Remp\BeamModule\Model\SegmentUser;
use Remp\BeamModule\Tests\TestCase;

class ComputeAuthorsSegmentsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigSeeder::class);
        $this->seed(SegmentGroupSeeder::class);
    }

    public function testJob()
    {
        Article::unsetEventDispatcher();

        $account = Account::factory()->create();
        $property = Property::factory()->create(['account_id' => $account->id]);
        $article = Article::factory()->create([
            'external_id' => 1,
            'property_uuid' => $property->uuid,
        ]);
        $author = Author::factory()->create();
        $article->authors()->save($author);

        $date = Carbon::today()->toDateString();

        // First we need aggregated data
        // These pageviews/timespent data are above defined author segments critera,
        // see job implementation for details
        $items = [
            [
                'article_id' => $article->id,
                'user_id' => null,
                'browser_id' => 'XYZ',
                'date' => $date,
                'pageviews' => 10,
                'timespent' => 1800 // 30 min
            ],
            [
                'article_id' => $article->id,
                'user_id' => '9',
                'browser_id' => 'ABC',
                'date' => $date,
                'pageviews' => 10,
                'timespent' => 1800 // 30 min
            ]
        ];
        ArticleAggregatedView::insert($items);

        $this->artisan(ComputeAuthorsSegments::COMMAND);

        $segment = Segment::where('code', 'author-' . $author->id)->first();
        $segmentBrowserIds = $segment->browsers->pluck('browser_id');
        $segmentUserIds = $segment->users->pluck('user_id');

        $this->assertContains('ABC', $segmentBrowserIds);
        $this->assertContains('XYZ', $segmentBrowserIds);
        $this->assertContains('9', $segmentUserIds);
    }

    public function testDeletingEmptySegments()
    {
        SegmentBrowser::query()->delete();
        SegmentUser::query()->delete();
        Segment::query()->delete();

        $s1 = Segment::factory()->author()->create();
        $s1->users()->save(SegmentUser::factory()->make());
        $s1->browsers()->save(SegmentBrowser::factory()->make());

        $s2 = Segment::factory()->author()->create();
        $s2->users()->save(SegmentUser::factory()->make());

        $s3 = Segment::factory()->author()->create();
        $s3->browsers()->save(SegmentBrowser::factory()->make());

        Segment::factory()->author()->create();

        $this->assertEquals(4, Segment::all()->count());

        ComputeAuthorsSegments::deleteEmptySegments();

        // Check that only segments without browsers and users were deleted
        $this->assertEquals(3, Segment::all()->count());
    }
}
