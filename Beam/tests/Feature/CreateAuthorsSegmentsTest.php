<?php

namespace Tests\Feature;

use App\ArticleBrowserView;
use App\Contracts\JournalContract;
use App\Contracts\Remp\Journal;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AggregateUserArticlesJobTest extends TestCase
{
    use RefreshDatabase;

    public function testJob()
    {
        $pageviews1 = <<<JSON
[
    {
        "count": 8,
        "tags": {
            "article_id": "2148518",
            "browser_id": "3f4e02bc-b140-4908-9971-6e4a686cb584"
        }
    },
    {
        "count": 3,
        "tags": {
            "article_id": "1148518",
            "browser_id": "3f4e02bc-b140-4908-9971-6e4a686cb584"
        }
    }
]
JSON;
        $pageviews2 = <<<JSON
[
    {
        "count": 3,
        "tags": {
            "article_id": "1148518",
            "browser_id": "3f4e02bc-b140-4908-9971-6e4a686cb584"
        }
    }
]
JSON;
        $pageviews3 = '[]';

        $timespent1 = <<<JSON
[
    {
        "sum": 10,
        "tags": {
            "article_id": "2148518",
            "browser_id": "3f4e02bc-b140-4908-9971-6e4a686cb584"
        }
    }
]
JSON;
        $timespent2 = <<<JSON
[
    {
        "sum": 25,
        "tags": {
            "article_id": "2148518",
            "browser_id": "3f4e02bc-b140-4908-9971-6e4a686cb584"
        }
    }
]
JSON;
        $timespent3 = '[]';

        // Mock Journal data
        // job aggregates pageviews day data in 24 1-hour windows
        $journalMock = Mockery::mock(Journal::class);
        $journalMock->shouldReceive('count')->andReturn(
            collect(json_decode($pageviews1)),
            collect(json_decode($pageviews2)),
            collect(json_decode($pageviews3))
        );

        // job aggregates timespent day data in 24 1-hour windows
        $journalMock->shouldReceive('sum')->andReturn(
            collect(json_decode($timespent1)),
            collect(json_decode($timespent2)),
            collect(json_decode($timespent3))
        );

        $this->app->instance(JournalContract::class, $journalMock);
        $this->artisan('pageviews:aggregate-user-articles');

        $articleView2148518 = ArticleBrowserView::where([
            'article_id' => '2148518',
            'browser_id' => '3f4e02bc-b140-4908-9971-6e4a686cb584'
        ])->first();

        $this->assertEquals(8, $articleView2148518->pageviews);
        $this->assertEquals(35, $articleView2148518->timespent);

        $articleView1148518 = ArticleBrowserView::where([
            'article_id' => '1148518',
            'browser_id' => '3f4e02bc-b140-4908-9971-6e4a686cb584'
        ])->first();

        $this->assertEquals(6, $articleView1148518->pageviews);
        $this->assertEquals(0, $articleView1148518->timespent);
    }
}
