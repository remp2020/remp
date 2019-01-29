<?php

namespace Tests\Feature;

use App\Account;
use App\Article;
use App\ArticleAggregatedView;
use App\Author;
use App\Console\Commands\ComputeAuthorsSegments;
use App\Mail\AuthorSegmentsResult;
use App\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
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
                'user_id' => '',
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

        Mail::fake();

        $this->artisan(ComputeAuthorsSegments::COMMAND, [
            '--min_views' => 0,
            '--min_average_timespent' => 0,
            '--min_ratio' => 0,
            'history' => 90,
            'email' => 'email@doesnt.matter'
        ]);

        Mail::assertSent(AuthorSegmentsResult::class);

        // TODO enable once segments computation is finalized
        //$segment = Segment::where('code', 'author-' . $author->id)->first();
        //$segmentBrowserIds = $segment->browsers->pluck('browser_id');
        //$segmentUserIds = $segment->users->pluck('user_id');
        //
        //$this->assertContains('ABC', $segmentBrowserIds);
        //$this->assertContains('XYZ', $segmentBrowserIds);
        //$this->assertContains('9', $segmentUserIds);
    }
}
