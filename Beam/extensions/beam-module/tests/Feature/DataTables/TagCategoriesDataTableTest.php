<?php

namespace Remp\BeamModule\Tests\Feature\DataTables;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\TagCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;
use Remp\BeamModule\Tests\TestCase;

class TagCategoriesDataTableTest extends TestCase
{
    use RefreshDatabase;

    /** @var Article[] */
    protected $articles;

    /** @var Tag[] */
    protected $tags;

    /** @var TagCategory[] */
    protected $tagCategories;

    public function setUp(): void
    {
        parent::setUp();

        Article::unsetEventDispatcher();

        $this->withoutMiddleware([
            VerifyJwtToken::class,
        ]);

        Property::factory()->create(['uuid' => 'prop_1']);
        Property::factory()->create(['uuid' => 'prop_2']);
        Property::factory()->create(['uuid' => 'prop_3']);
        Property::factory()->create(['uuid' => 'prop_4']);

        $this->articles = [
            'prop1' => Article::factory()->create(['property_uuid' => 'prop_1']),
            'prop1_shared' => Article::factory()->create(['property_uuid' => 'prop_1']),
            'prop2' => Article::factory()->create(['property_uuid' => 'prop_2']),
            'prop2_shared' => Article::factory()->create(['property_uuid' => 'prop_2']),
            'prop3' => Article::factory()->create(['property_uuid' => 'prop_3']),
            'prop4' => Article::factory()->create(['property_uuid' => 'prop_4']),
        ];

        $this->tags = [
            1 => Tag::factory()->create(),
            2 => Tag::factory()->create(),
            3 => Tag::factory()->create(),
            4 => Tag::factory()->create(),
        ];

        $this->tagCategories = [
            1 => TagCategory::factory()->create(),
            2 => TagCategory::factory()->create(),
            3 => TagCategory::factory()->create(),  // has prop_3 only tag
        ];

        // assign tag categories
        $this->tags[1]->tagCategories()->attach($this->tagCategories[1]);
        $this->tags[1]->tagCategories()->attach($this->tagCategories[2]);
        $this->tags[2]->tagCategories()->attach($this->tagCategories[2]);
        $this->tags[3]->tagCategories()->attach($this->tagCategories[3]);
        $this->tags[4]->tagCategories()->attach($this->tagCategories[3]);

        // assign tags
        $this->articles['prop1']->tags()->attach($this->tags[1]);
        $this->articles['prop1_shared']->tags()->attach($this->tags[1]);
        $this->articles['prop1_shared']->tags()->attach($this->tags[2]);
        $this->articles['prop2']->tags()->attach($this->tags[2]);
        $this->articles['prop2_shared']->tags()->attach($this->tags[1]);
        $this->articles['prop2_shared']->tags()->attach($this->tags[2]);
        $this->articles['prop3']->tags()->attach($this->tags[3]);
        $this->articles['prop4']->tags()->attach($this->tags[4]);

        // assign conversions
        $this->articles['prop1']->conversions()->saveMany(
            Conversion::factory()->count(1)->make(['article_id' => $this->articles['prop1']])
        );
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

    public function testAllTagCategories()
    {
        $json = $this->requestTagCategories();
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 3);
        $json->assertJsonPath('data.0.id', $this->tagCategories[2]->id);
        $json->assertJsonPath('data.0.tags_count', 2);
        $json->assertJsonPath('data.0.articles_count', 4);
        $json->assertJsonPath('data.0.conversions_count', 10);
        $json->assertJsonPath('data.1.id', $this->tagCategories[1]->id);
        $json->assertJsonPath('data.1.tags_count', 1);
        $json->assertJsonPath('data.1.articles_count', 3);
        $json->assertJsonPath('data.1.conversions_count', 7);
        $json->assertJsonPath('data.2.id', $this->tagCategories[3]->id);
        $json->assertJsonPath('data.2.tags_count', 2);
        $json->assertJsonPath('data.2.articles_count', 2);
        $json->assertJsonPath('data.2.conversions_count', 0);
    }

    public function testPropertyTagCategories()
    {
        $this->setProperty('prop_1');
        $json = $this->requestTagCategories();
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->tagCategories[1]->id);
        $json->assertJsonPath('data.0.tags_count', 1);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 3);
        $json->assertJsonPath('data.1.id', $this->tagCategories[2]->id);
        $json->assertJsonPath('data.1.tags_count', 2);
        $json->assertJsonPath('data.1.articles_count', 2);
        $json->assertJsonPath('data.1.conversions_count', 3);

        $this->setProperty('prop_2');
        $json = $this->requestTagCategories();
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->tagCategories[2]->id);
        $json->assertJsonPath('data.0.tags_count', 2);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 7);
        $json->assertJsonPath('data.1.id', $this->tagCategories[1]->id);
        $json->assertJsonPath('data.1.tags_count', 1);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 4);

        $this->setProperty('prop_3');
        $json = $this->requestTagCategories();
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 1);
        $json->assertJsonPath('data.0.id', $this->tagCategories[3]->id);
        $json->assertJsonPath('data.0.tags_count', 1);
        $json->assertJsonPath('data.0.articles_count', 1);
        $json->assertJsonPath('data.0.conversions_count', 0);
    }

    public function testAllTagCategoryArticles()
    {
        $json = $this->requestTagCategoryArticles($this->tagCategories[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 3);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);
        $json->assertJsonPath('data.1.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.1.conversions_count', 2);
        $json->assertJsonPath('data.2.id', $this->articles['prop1']->id);
        $json->assertJsonPath('data.2.conversions_count', 1);

        $json = $this->requestTagCategoryArticles($this->tagCategories[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 4);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);
        $json->assertJsonPath('data.1.id', $this->articles['prop2']->id);
        $json->assertJsonPath('data.1.conversions_count', 3);
        $json->assertJsonPath('data.2.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.2.conversions_count', 2);
        $json->assertJsonPath('data.3.id', $this->articles['prop1']->id);
        $json->assertJsonPath('data.3.conversions_count', 1);
    }

    public function testPropertyTagCategoryArticles()
    {
        $this->setProperty('prop_1');
        $json = $this->requestTagCategoryArticles($this->tagCategories[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 2);
        $json->assertJsonPath('data.1.id', $this->articles['prop1']->id);
        $json->assertJsonPath('data.1.conversions_count', 1);

        $this->setProperty('prop_2');
        $json = $this->requestTagCategoryArticles($this->tagCategories[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 1);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);

        $this->setProperty('prop_1');
        $json = $this->requestTagCategoryArticles($this->tagCategories[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->articles['prop1_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 2);
        $json->assertJsonPath('data.1.id', $this->articles['prop1']->id);
        $json->assertJsonPath('data.1.conversions_count', 1);

        $this->setProperty('prop_2');
        $json = $this->requestTagCategoryArticles($this->tagCategories[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('recordsTotal', 2);
        $json->assertJsonPath('data.0.id', $this->articles['prop2_shared']->id);
        $json->assertJsonPath('data.0.conversions_count', 4);
        $json->assertJsonPath('data.1.id', $this->articles['prop2']->id);
        $json->assertJsonPath('data.1.conversions_count', 3);
    }

    public function testAllTagCategoryTags()
    {
        $json = $this->requestTagCategoryTags($this->tagCategories[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[1]->id);
        $json->assertJsonPath('data.0.articles_count', 3);
        $json->assertJsonPath('data.0.conversions_count', 7);

        $json = $this->requestTagCategoryTags($this->tagCategories[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[2]->id);
        $json->assertJsonPath('data.0.articles_count', 3);
        $json->assertJsonPath('data.0.conversions_count', 9);
        $json->assertJsonPath('data.1.id', $this->tags[1]->id);
        $json->assertJsonPath('data.1.articles_count', 3);
        $json->assertJsonPath('data.1.conversions_count', 7);
    }

    public function testPropertyTagCategoryTags()
    {
        $this->setProperty('prop_1');
        $json = $this->requestTagCategoryTags($this->tagCategories[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[1]->id);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 3);

        $this->setProperty('prop_2');
        $json = $this->requestTagCategoryTags($this->tagCategories[1]);
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[1]->id);
        $json->assertJsonPath('data.0.articles_count', 1);
        $json->assertJsonPath('data.0.conversions_count', 4);

        $this->setProperty('prop_1');
        $json = $this->requestTagCategoryTags($this->tagCategories[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[1]->id);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 3);
        $json->assertJsonPath('data.1.id', $this->tags[2]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 2);

        $this->setProperty('prop_2');
        $json = $this->requestTagCategoryTags($this->tagCategories[2]);
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->tags[2]->id);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 7);
        $json->assertJsonPath('data.1.id', $this->tags[1]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 4);
    }

    private function setProperty(string $property)
    {
        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken($property);
    }

    private function requestTagCategories()
    {
        return $this->getJson(route('tagCategories.dtTagCategories', [
            'columns[0][data]' => 'conversions_count',
            'columns[1][data]' => 'id',
            'order[0][column]' => 0,
            'order[0][dir]' => 'desc',
            'order[1][column]' => 1,
            'order[1][dir]' => 'asc',
        ]));
    }

    private function requestTagCategoryArticles(TagCategory $tagCategory)
    {
        return $this->getJson(route('tagCategories.dtArticles', [
            'tagCategory' => $tagCategory->id,
            'columns[0][data]' => 'conversions_count',
            'columns[1][data]' => 'id',
            'order[0][column]' => 0,
            'order[0][dir]' => 'desc',
            'order[1][column]' => 1,
            'order[1][dir]' => 'asc',
        ]));
    }

    private function requestTagCategoryTags(TagCategory $tagCategory)
    {
        return $this->getJson(route('tagCategories.dtTags', [
            'tagCategory' => $tagCategory->id,
            'columns[0][data]' => 'conversions_count',
            'columns[1][data]' => 'id',
            'order[0][column]' => 0,
            'order[0][dir]' => 'desc',
            'order[1][column]' => 1,
            'order[1][dir]' => 'asc',
        ]));
    }
}
