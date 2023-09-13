<?php

namespace Remp\BeamModule\Tests\Feature\API;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\Property;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;
use Remp\BeamModule\Tests\TestCase;

class ConversionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Article::unsetEventDispatcher();

        $this->withoutMiddleware([
            VerifyJwtToken::class,
        ]);

        Property::factory()->create(['uuid' => 'prop_1']);
        Property::factory()->create(['uuid' => 'prop_2']);

        $articles = [
            'prop1' => Article::factory()->create(['property_uuid' => 'prop_1']),
            'prop2' => Article::factory()->create(['property_uuid' => 'prop_2']),
        ];

        // assign conversions
        $articles['prop1']->conversions()->save(
            Conversion::factory()->create(['article_id' => $articles['prop1'], 'paid_at' => new Carbon("2021-11-08T10:00:00")])
        );
        $articles['prop1']->conversions()->save(
            Conversion::factory()->create(['article_id' => $articles['prop1'], 'paid_at' => new Carbon("2021-11-07T10:00:00")])
        );

        $articles['prop2']->conversions()->save(
            Conversion::factory()->create(['article_id' => $articles['prop2'], 'paid_at' => new Carbon("2021-11-06T10:00:00")])
        );
        $articles['prop2']->conversions()->save(
            Conversion::factory()->create(['article_id' => $articles['prop2'], 'paid_at' => new Carbon("2021-11-05T10:00:00")])
        );
        $articles['prop2']->conversions()->save(
            Conversion::factory()->create(['article_id' => $articles['prop2'], 'paid_at' => new Carbon("2021-11-04T10:00:00")])
        );
    }

    public function testConversionFilteredByConversionTime()
    {
        $json = $this->request(['conversion_from' => "2021-11-05T00:00:00"]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 4);

        $json = $this->request([
            'conversion_from' => "2021-11-05T00:00:00",
            'conversion_to' => "2021-11-07T23:59:59"
        ]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 3);

        $json = $this->request(['conversion_to' => '2021-11-06T00:00:00']);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
    }

    private function request(array $parameters = [])
    {
        return $this->getJson(route('conversions.json', $parameters));
    }
}
