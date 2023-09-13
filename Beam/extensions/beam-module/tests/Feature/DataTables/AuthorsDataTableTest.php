<?php

namespace Remp\BeamModule\Tests\Feature\DataTables;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\BeamModule\Model\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;
use Remp\BeamModule\Tests\TestCase;

class AuthorsDataTableTest extends TestCase
{
    use RefreshDatabase;

    protected $authors;

    public function setUp(): void
    {
        parent::setUp();

        Article::unsetEventDispatcher();

        $this->withoutMiddleware([
            VerifyJwtToken::class,
        ]);

        Property::factory()->create(['uuid' => 'prop_1']);
        Property::factory()->create(['uuid' => 'prop_2']);

        /** @var Article $prop1SharedArticle */
        $prop1SharedArticle = Article::factory()->create(['property_uuid' => 'prop_1', 'content_type' => 'article']);
        /** @var Article $prop2Article */
        $prop2Article = Article::factory()->create(['property_uuid' => 'prop_2', 'content_type' => 'article']);
        /** @var Article $prop2SharedArticle */
        $prop2SharedArticle = Article::factory()->create(['property_uuid' => 'prop_2', 'content_type' => 'blog']);

        $this->authors = [
            1 => Author::factory()->create(),
            2 => Author::factory()->create(),
        ];

        // assign authors
        $prop1SharedArticle->authors()->attach($this->authors[1]);
        $prop1SharedArticle->authors()->attach($this->authors[2]);
        $prop2Article->authors()->attach($this->authors[2]);
        $prop2SharedArticle->authors()->attach($this->authors[1]);
        $prop2SharedArticle->authors()->attach($this->authors[2]);

        // assign conversions
        $prop1SharedArticle->conversions()->saveMany(
            Conversion::factory()->count(2)->make(['article_id' => $prop1SharedArticle])
        );
        $prop2Article->conversions()->saveMany(
            Conversion::factory()->count(3)->make(['article_id' => $prop2Article])
        );
        $prop2SharedArticle->conversions()->saveMany(
            Conversion::factory()->count(4)->make(['article_id' => $prop2SharedArticle])
        );
    }

    public function testAllAuthors()
    {
        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->authors[2]->id);
        $json->assertJsonPath('data.0.articles_count', 3);
        $json->assertJsonPath('data.0.conversions_count', 9);
        $json->assertJsonPath('data.1.id', $this->authors[1]->id);
        $json->assertJsonPath('data.1.articles_count', 2);
        $json->assertJsonPath('data.1.conversions_count', 6);
    }

    public function testPropertyAuthors()
    {
        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_1');

        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->authors[1]->id);
        $json->assertJsonPath('data.0.articles_count', 1);
        $json->assertJsonPath('data.0.conversions_count', 2);
        $json->assertJsonPath('data.1.id', $this->authors[2]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 2);

        /** @var SelectedProperty $selectedProperty */
        $selectedProperty = resolve(SelectedProperty::class);
        $selectedProperty->setToken('prop_2');

        $json = $this->request();
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->authors[2]->id);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 7);
        $json->assertJsonPath('data.1.id', $this->authors[1]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 4);
    }

    public function testAuthorsContentType()
    {
        $json = $this->request('article');
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->authors[2]->id);
        $json->assertJsonPath('data.0.articles_count', 2);
        $json->assertJsonPath('data.0.conversions_count', 5);
        $json->assertJsonPath('data.1.id', $this->authors[1]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 2);

        $json = $this->request('blog');
        $json->assertSuccessful();
        $json->assertJsonPath('data.0.id', $this->authors[1]->id);
        $json->assertJsonPath('data.0.articles_count', 1);
        $json->assertJsonPath('data.0.conversions_count', 4);
        $json->assertJsonPath('data.1.id', $this->authors[2]->id);
        $json->assertJsonPath('data.1.articles_count', 1);
        $json->assertJsonPath('data.1.conversions_count', 4);
    }

    private function request(string $contentType = null)
    {
        return $this->getJson(route('authors.dtAuthors', [
            'content_type' => $contentType,
            'columns[0][data]' => 'articles_count',
            'columns[1][data]' => 'id',
            'order[0][column]' => 0,
            'order[0][dir]' => 'desc',
            'order[1][column]' => 1,
            'order[1][dir]' => 'asc',
        ]));
    }
}
