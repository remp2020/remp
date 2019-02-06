<?php

namespace Tests\Feature;

use App\Account;
use App\Article;
use App\ArticleAggregatedView;
use App\Author;
use App\Console\Commands\ComputeAuthorsSegments;
use App\Property;
use App\Segment;
use App\SegmentBrowser;
use App\SegmentUser;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComputeAuthorsSegmentsTest extends TestCase
{
    use RefreshDatabase;

    public function testJob()
    {
        $account = factory(Account::class)->create();
        $property = factory(Property::class)->create(['account_id' => $account->id]);
        $article = factory(Article::class)->create([
            'external_id' => 1,
            'property_uuid' => $property->uuid,
        ]);
        $author = factory(Author::class)->create();
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

        $s1 = factory(Segment::class)->state('author')->create();
        $s1->users()->save(factory(SegmentUser::class)->make());
        $s1->browsers()->save(factory(SegmentBrowser::class)->make());

        $s2 = factory(Segment::class)->state('author')->create();
        $s2->users()->save(factory(SegmentUser::class)->make());

        $s3 = factory(Segment::class)->state('author')->create();
        $s3->browsers()->save(factory(SegmentBrowser::class)->make());

        factory(Segment::class)->state('author')->create();

        $this->assertEquals(4, Segment::all()->count());

        ComputeAuthorsSegments::deleteEmptySegments();

        // Check that only segments without browsers and users were deleted
        $this->assertEquals(3, Segment::all()->count());
    }
}
