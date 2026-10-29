<?php

namespace Remp\BeamModule\Tests\Feature\API;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\TagCategory;
use Remp\BeamModule\Tests\TestCase;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;

class ArticlesListApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Article::unsetEventDispatcher();

        $this->withoutMiddleware([
            Authenticate::class,
            VerifyJwtToken::class,
        ]);

        Property::factory()->create(['uuid' => 'test_property_uuid']);
    }

    public function testRelationNamesAreReturned(): void
    {
        $article = $this->createArticleWithRelations('article_1', tagsCount: 1);

        $response = $this->getJson(route('api.articles.index', ['external_ids' => 'article_1']));

        $response->assertSuccessful();
        $response->assertJsonPath('0.authors.0.name', $article->authors->first()->name);
        $response->assertJsonPath('0.sections.0.name', $article->sections->first()->name);
        $response->assertJsonPath('0.tags.0.name', $article->tags->first()->name);
        $this->assertEqualsCanonicalizing(
            $article->tags->first()->tagCategories->pluck('name')->all(),
            $response->json('0.tags.0.tag_categories.*.name')
        );
    }

    public function testQueryCountDoesNotGrowWithArticlesAndTags(): void
    {
        $this->createArticleWithRelations('article_1', tagsCount: 1);
        $this->createArticleWithRelations('article_2', tagsCount: 3);
        $this->createArticleWithRelations('article_3', tagsCount: 3);

        $singleArticleQueries = $this->countQueries(['external_ids' => 'article_1']);
        $multipleArticlesQueries = $this->countQueries(['external_ids' => 'article_1,article_2,article_3']);

        $this->assertSame($singleArticleQueries, $multipleArticlesQueries);
    }

    #[DataProvider('paginatedJsonRoutes')]
    public function testPaginatedJsonQueryCountDoesNotGrowWithArticlesAndTags(string $route): void
    {
        $this->createArticleWithRelations('article_1', tagsCount: 1);
        $singleArticleQueries = $this->countQueries([], $route);

        $this->createArticleWithRelations('article_2', tagsCount: 3);
        $this->createArticleWithRelations('article_3', tagsCount: 3);
        $multipleArticlesQueries = $this->countQueries([], $route);

        $this->assertSame($singleArticleQueries, $multipleArticlesQueries);
    }

    public static function paginatedJsonRoutes(): array
    {
        return [
            'pageviews' => ['articles.pageviews'],
            'conversions' => ['articles.conversions'],
        ];
    }

    private function createArticleWithRelations(string $externalId, int $tagsCount): Article
    {
        $article = Article::factory()->create([
            'external_id' => $externalId,
            'property_uuid' => 'test_property_uuid',
        ]);

        $article->authors()->attach(Author::factory()->create(['external_id' => "{$externalId}_author"]));
        $article->sections()->attach(Section::factory()->create(['external_id' => "{$externalId}_section"]));

        for ($i = 1; $i <= $tagsCount; $i++) {
            $tag = Tag::factory()->create(['external_id' => "{$externalId}_tag_{$i}"]);
            $tag->tagCategories()->attach(TagCategory::factory()->count(2)->create());
            $article->tags()->attach($tag);
        }

        return $article;
    }

    private function countQueries(array $parameters, string $route = 'api.articles.index'): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson(route($route, $parameters))->assertSuccessful();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
