<?php

namespace Remp\BeamModule\Tests\Feature\DataTables;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\BeamModule\Model\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;
use Remp\BeamModule\Tests\TestCase;

class ArticlePageviewsDataTableTest extends TestCase
{
    use RefreshDatabase;

    /** @var Article[] */
    protected $articles;

    public function setUp(): void
    {
        parent::setUp();

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
    }

    public function testAllArticles()
    {
        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->articles['prop1']->id);
        $json->assertJsonPath('data.1.id', $this->articles['prop2']->id);
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

        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_2');

        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJson(['recordsTotal' => 1]);
        $json->assertJsonPath('data.0.id', $this->articles['prop2']->id);
    }

    private function request()
    {
        return $this->getJson(route('articles.dtPageviews', [
            'columns[0][data]' => 'id',
            'order[0][column]' => 0,
            'order[0][dir]' => 'asc',
        ]));
    }
}
