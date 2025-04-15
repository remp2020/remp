<?php

namespace Remp\BeamModule\Tests\Feature\DataTables;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\BeamModule\Database\Seeders\ConfigSeeder;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\BeamModule\Tests\TestCase;
use Remp\Journal\JournalContract;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;

class ArticleConversionsDataTableTest extends TestCase
{
    use RefreshDatabase;

    /** @var Article[] */
    protected $articles;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(ConfigSeeder::class);
        Article::unsetEventDispatcher();

        $this->withoutMiddleware([
            VerifyJwtToken::class,
        ]);

        Property::factory()->create(['uuid' => 'prop_1']);
        Property::factory()->create(['uuid' => 'prop_2']);

        $this->articles = [
            'prop1' => Article::factory()->create(['property_uuid' => 'prop_1']),
            'prop2' => Article::factory()->create(['property_uuid' => 'prop_2']),
        ];

        // assign conversions
        $this->articles['prop1']->conversions()->saveMany(
            Conversion::factory()->count(2)->make(['article_id' => $this->articles['prop1']])
        );
        $this->articles['prop2']->conversions()->saveMany(
            Conversion::factory()->count(3)->make(['article_id' => $this->articles['prop2']])
        );

        $journalMock = \Mockery::mock(JournalContract::class);
        // mock unavailable unique browser counts so the conversion_rate calculation can proceed
        $journalMock->shouldReceive('unique')->andReturn([]);
        $this->app->instance(JournalContract::class, $journalMock);
    }

    public function testAllArticles()
    {
        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->articles['prop2']->id);
        $json->assertJsonPath('data.0.conversions_count', 3);
        $json->assertJsonPath('data.1.id', $this->articles['prop1']->id);
        $json->assertJsonPath('data.1.conversions_count', 2);
    }

    public function testPropertyArticles()
    {
        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_1');

        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJson(['recordsTotal' => 1]);
        $json->assertJsonPath('data.0.id', $this->articles['prop1']->id);
        $json->assertJsonPath('data.0.conversions_count', 2);

        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_2');

        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJson(['recordsTotal' => 1]);
        $json->assertJsonPath('data.0.id', $this->articles['prop2']->id);
        $json->assertJsonPath('data.0.conversions_count', 3);
    }

    private function request()
    {
        return $this->getJson(route('articles.dtConversions', [
            'columns[0][data]' => 'conversions_count',
            'order[0][column]' => 0,
            'order[0][dir]' => 'desc',
        ]));
    }
}
