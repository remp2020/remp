<?php

namespace Tests\Feature;

use App\Article;
use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Console\Commands\CompressAggregations;
use App\Property;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompressAggregationsTest extends TestCase
{
    use RefreshDatabase;

    public function testPageviewsAggregations()
    {
        $this->runAndTestAggregations(ArticlePageviews::class);
    }

    public function testTimespentAggregations()
    {
        $this->runAndTestAggregations(ArticleTimespent::class);
    }

    public function runAndTestAggregations($className)
    {
        $property = factory(Property::class)->create();

        $article1 = factory(Article::class)->create(['property_uuid' => $property->uuid]);
        $article2 = factory(Article::class)->create(['property_uuid' => $property->uuid]);

        // Prepare data to be aggregated
        $ag1 = factory($className)->create([
            'article_id' => $article1->id,
            'time_from' => Carbon::today()->hour(9)->subDays(91),
            'time_to' => Carbon::today()->hour(10)->subDays(91)
        ]);

        $ag2 = factory($className)->create([
            'article_id' => $article1->id,
            'time_from' => Carbon::today()->hour(10)->subDays(91),
            'time_to' => Carbon::today()->hour(11)->subDays(91),
        ]);

        factory($className)->create([
            'article_id' => $article2->id,
            'time_from' => Carbon::today()->hour(10)->subDays(91),
            'time_to' => Carbon::today()->hour(11)->subDays(91)
        ]);

        // These won't be aggregated
        factory($className)->create([
            'article_id' => $article1->id,
            'time_from' => Carbon::today()->hour(10)->subDays(90),
            'time_to' => Carbon::today()->hour(11)->subDays(90),
        ]);

        factory($className)->create([
            'article_id' => $article1->id,
            'time_from' => Carbon::today()->hour(10)->subDays(89),
            'time_to' => Carbon::today()->hour(11)->subDays(89),
        ]);

        // Prepare asserts
        $runAsserts = function () use ($ag1, $ag2, $article1, $article2, $className) {
            $ag = $className::where('article_id', $article1->id)
                ->where('time_to', '<=', Carbon::today()->subDays(90))->first();

            $this->assertEquals($ag1->sum + $ag2->sum, $ag->sum);
            $this->assertEquals($ag1->signed_in + $ag2->signed_in, $ag->signed_in);
            $this->assertEquals($ag1->subscribers + $ag2->subscribers, $ag->subscribers);

            $this->assertEquals(3, $className::where('article_id', $article1->id)->count());
            $this->assertEquals(1, $className::where('article_id', $article2->id)->count());
        };

        // Run test
        $this->artisan(CompressAggregations::COMMAND);
        $runAsserts();

        // Make sure when command is run twice, results are the same
        $this->artisan(CompressAggregations::COMMAND);
        $runAsserts();
    }

}
