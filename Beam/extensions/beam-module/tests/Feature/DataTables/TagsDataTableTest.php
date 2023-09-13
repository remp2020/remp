<?php

namespace Remp\BeamModule\Tests\Feature\DataTables;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;
use Remp\BeamModule\Tests\TestCase;

class TagsDataTableTest extends TestCase
{
    use RefreshDatabase;

    /** @var Article[] */
    protected $articles;

    /** @var Tag[] */
    protected $tags;

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
            'prop1_shared' => Article::factory()->create(['property_uuid' => 'prop_1']),
            'prop2' => Article::factory()->create(['property_uuid' => 'prop_2']),
            'prop2_shared' => Article::factory()->create(['property_uuid' => 'prop_2']),
        ];

        $this->tags = [
            1 => Tag::factory()->create(),
            2 => Tag::factory()->create(),
        ];

        // assign tags
        $this->articles['prop1_shared']->tags()->attach($this->tags[1]);
        $this->articles['prop1_shared']->tags()->attach($this->tags[2]);
        $this->articles['prop2']->tags()->attach($this->tags[2]);
        $this->articles['prop2_shared']->tags()->attach($this->tags[1]);
        $this->articles['prop2_shared']->tags()->attach($this->tags[2]);

        // assign conversions
        $this->articles['prop1_shared']->conversions()->saveMany(
            Conversion::factory()->count(2)->make(['article_id' => $this->articles['prop1_shared']])
        );
        $this->articles['prop2']->conversions()->saveMany(
            Conversion::factory()->count(3)->make(['article_id' => $this->articles['prop2']])
        );
        $this->articles['prop2_shared']->conversions()->saveMany(
            Conversion::factory()->count(4)->make(['article_id' => $this->articles['prop2_shared']])
        );
    }

    public function testAllTags()
    {
        $json = $this->requestTags();
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[2]->id);
        $json->assertJsonPath('data.0.articles_count', 3);
        $json->assertJsonPath('data.0.conversions_count', 9);
        $json->assertJsonPath('data.1.id', $this->tags[1]->id);
        $json->assertJsonPath('data.1.articles_count', 2);
        $json->assertJsonPath('data.1.conversions_count', 6);
    }

    public function testPropertyTags()
    {
        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_1');

        $json = $this->requestTags();
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[1]->id);
        $json->assertJsonPath('data.0.articles_count', 1);
        $json->assertJsonPath('data.0.conversions_count', 2);
        $json->assertJsonPath('data.1.id', $this->tags[2]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 2);

        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_2');

        $json = $this->requestTags();
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[2]->id);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 7);
        $json->assertJsonPath('data.1.id', $this->tags[1]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 4);
    }

    public function testAllTagArticles()
    {
        $json = $this->requestSectionTags($this->tags[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);
        $json->assertJsonPath('data.1.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.1.conversions_count', 2);

        $json = $this->requestSectionTags($this->tags[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 3);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);
        $json->assertJsonPath('data.1.id', $this->articles['prop2']->id);
        $json->assertJsonPath('data.1.conversions_count', 3);
        $json->assertJsonPath('data.2.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.2.conversions_count', 2);
    }

    public function testPropertyTagArticles()
    {
        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_1');

        $json = $this->requestSectionTags($this->tags[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 1);
        $json->assertJsonPath('data.0.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 2);

        $json = $this->requestSectionTags($this->tags[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 1);
        $json->assertJsonPath('data.0.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 2);

        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_2');

        $json = $this->requestSectionTags($this->tags[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 1);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);

        $json = $this->requestSectionTags($this->tags[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);
        $json->assertJsonPath('data.1.id', $this->articles['prop2']->id);
        $json->assertJsonPath('data.1.conversions_count', 3);
    }

    private function requestTags()
    {
        return $this->getJson(route('tags.dtTags', [
            'columns[0][data]' => 'conversions_count',
            'columns[1][data]' => 'id',
            'order[0][column]' => 0,
            'order[0][dir]' => 'desc',
            'order[1][column]' => 1,
            'order[1][dir]' => 'asc',
        ]));
    }

    private function requestSectionTags(Tag $tag)
    {
        return $this->getJson(route('tags.dtArticles', [
            'tag' => $tag->id,
            'columns[0][data]' => 'conversions_count',
            'columns[1][data]' => 'id',
            'order[0][column]' => 0,
            'order[0][dir]' => 'desc',
            'order[1][column]' => 1,
            'order[1][dir]' => 'asc',
        ]));
    }
}
