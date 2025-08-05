<?php

namespace Remp\BeamModule\Tests\Feature\API;

use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Tests\TestCase;

class TopSearchTest extends TestCase
{
    use RefreshDatabase;

    private array $articles = [];

    public function setUp(): void
    {
        parent::setUp();

        Article::unsetEventDispatcher();

        $this->withoutMiddleware([
            Authenticate::class,
        ]);

        Property::factory()->create(['uuid' => 'test_property_uuid']);

        $this->articles = [
            'ar1' => Article::factory()->create([
                'property_uuid' => 'test_property_uuid',
                'external_id' => 'ar1',
                'published_at' => new Carbon("2021-11-01T10:00:00"),
                'pageviews_all' => 1000
            ]),
            'ar2' => Article::factory()->create([
                'property_uuid' => 'test_property_uuid',
                'external_id' => 'ar2',
                'published_at' => new Carbon("2021-11-10T10:00:00"),
                'pageviews_all' => 2000
            ]),
            'ar3' => Article::factory()->create([
                'property_uuid' => 'test_property_uuid',
                'external_id' => 'ar3',
                'published_at' => new Carbon("2021-11-02T10:00:00"),
                'pageviews_all' => 1500
            ]),
            'ar4' => Article::factory()->create([
                'property_uuid' => 'test_property_uuid',
                'external_id' => 'ar4',
                'published_at' => new Carbon("2021-11-12T10:00:00"),
                'pageviews_all' => 3000
            ]),
        ];
    }

    public static function byPublishedAtDataProvider(): array
    {
        return [
            'ByPublishFrom' => [
                'requestParams' => [
                    'from' => "2021-11-01T00:00:00",
                    'limit' => 10,
                    'published_from' => "2021-11-05T00:00:00"
                ],
                'expectedArticleKeys' => ['ar4', 'ar2'],
                'expectedCount' => 2
            ],
            'ByPublishedTo' => [
                'requestParams' => [
                    'from' => "2021-11-01T00:00:00",
                    'limit' => 10,
                    'published_to' => "2021-11-05T00:00:00"
                ],
                'expectedArticleKeys' => ['ar3', 'ar1'],
                'expectedCount' => 2
            ],
            'ByPublishedFromAndTo' => [
                'requestParams' => [
                    'from' => "2021-11-01T00:00:00",
                    'limit' => 10,
                    'published_from' => "2021-11-02T00:00:00",
                    'published_to' => "2021-11-11T00:00:00"
                ],
                'expectedArticleKeys' => ['ar2', 'ar3'],
                'expectedCount' => 2
            ],
            'WithoutPublishedFilters' => [
                'requestParams' => [
                    'from' => "2021-11-01T00:00:00",
                    'limit' => 10
                ],
                'expectedArticleKeys' => ['ar4', 'ar2', 'ar3', 'ar1'],
                'expectedCount' => 4
            ],
        ];
    }

    #[DataProvider('byPublishedAtDataProvider')]
    public function testTopSearchApi(array $requestParams, array $expectedArticleKeys, int $expectedCount)
    {
        $response = $this->request($requestParams);
        $response->assertSuccessful();
        
        $data = $response->json();
        $this->assertCount($expectedCount, $data);
        
        foreach ($expectedArticleKeys as $i => $expectedKey) {
            $this->assertEquals($expectedKey, $data[$i]['external_id']);
        }
    }

    private function request(array $parameters = [])
    {
        return $this->postJson(route('articles.top.v2'), $parameters);
    }
}