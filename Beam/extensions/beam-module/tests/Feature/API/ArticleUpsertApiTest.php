<?php

namespace Remp\BeamModule\Tests\Feature\API;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\TagCategory;
use Remp\BeamModule\Tests\TestCase;

class ArticleUpsertApiTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        Article::unsetEventDispatcher();

        $this->withoutMiddleware([
            Authenticate::class,
        ]);

        $this->property = Property::factory()->create(['uuid' => 'test_property_uuid']);
    }

    // =========================================================================
    // Basic Article Operations
    // =========================================================================

    public function testCreateNewArticle(): void
    {
        $response = $this->postJson(route('api.articles.upsert.v2'), [
            'articles' => [
                $this->makeArticlePayload(),
            ],
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('articles', [
            'external_id' => 'article_1',
            'title' => 'Test Article',
            'url' => 'https://example.com/article-1',
        ]);
    }

    public function testUpdateExistingArticle(): void
    {
        $this->upsertArticles([$this->makeArticlePayload(['title' => 'Original Title'])]);
        $this->upsertArticles([$this->makeArticlePayload(['title' => 'Updated Title'])]);

        $this->assertDatabaseCount('articles', 1);
        $this->assertDatabaseHas('articles', [
            'external_id' => 'article_1',
            'title' => 'Updated Title',
        ]);
    }

    public function testUpsertMultipleArticles(): void
    {
        $response = $this->upsertArticles([
            $this->makeArticlePayload([
                'external_id' => 'article_1',
                'title' => 'Article 1',
                'authors' => [['external_id' => 'author_1', 'name' => 'John Doe']],
            ]),
            $this->makeArticlePayload([
                'external_id' => 'article_2',
                'title' => 'Article 2',
                'url' => 'https://example.com/article-2',
                'authors' => [
                    ['external_id' => 'author_1', 'name' => 'John Doe'],
                    ['external_id' => 'author_2', 'name' => 'Jane Smith'],
                ],
            ]),
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseCount('authors', 2);

        $this->assertCount(1, Article::where('external_id', 'article_1')->first()->authors);
        $this->assertCount(2, Article::where('external_id', 'article_2')->first()->authors);
    }

    public static function contentTypeDataProvider(): array
    {
        return [
            'DefaultContentType' => [null, 'article'],
            'CustomContentType' => ['blog', 'blog'],
        ];
    }

    #[DataProvider('contentTypeDataProvider')]
    public function testArticleContentType(?string $inputContentType, string $expectedContentType): void
    {
        $payload = $this->makeArticlePayload();
        if ($inputContentType !== null) {
            $payload['content_type'] = $inputContentType;
        }

        $this->upsertArticles([$payload]);

        $this->assertDatabaseHas('articles', [
            'external_id' => 'article_1',
            'content_type' => $expectedContentType,
        ]);
    }

    // =========================================================================
    // Relations: Create with relations
    // =========================================================================

    public static function createWithRelationsDataProvider(): array
    {
        return [
            'Authors' => [
                'relation' => 'authors',
                'relationData' => [
                    ['external_id' => 'author_1', 'name' => 'John Doe'],
                    ['external_id' => 'author_2', 'name' => 'Jane Smith'],
                ],
                'expectedNames' => ['John Doe', 'Jane Smith'],
            ],
            'Sections' => [
                'relation' => 'sections',
                'relationData' => [
                    ['external_id' => 'section_1', 'name' => 'Technology'],
                    ['external_id' => 'section_2', 'name' => 'Business'],
                ],
                'expectedNames' => ['Technology', 'Business'],
            ],
            'Tags' => [
                'relation' => 'tags',
                'relationData' => [
                    ['external_id' => 'tag_1', 'name' => 'PHP'],
                    ['external_id' => 'tag_2', 'name' => 'Laravel'],
                ],
                'expectedNames' => ['PHP', 'Laravel'],
            ],
        ];
    }

    #[DataProvider('createWithRelationsDataProvider')]
    public function testCreateArticleWithRelations(
        string $relation,
        array $relationData,
        array $expectedNames
    ): void {
        $response = $this->upsertArticles([
            $this->makeArticlePayload([$relation => $relationData]),
        ]);

        $response->assertSuccessful();

        $article = Article::where('external_id', 'article_1')->first();
        $this->assertCount(count($expectedNames), $article->$relation);
        $this->assertEqualsCanonicalizing($expectedNames, $article->$relation->pluck('name')->toArray());
    }

    // =========================================================================
    // Relations: Add, Remove, Replace
    // =========================================================================

    public static function relationOperationsDataProvider(): array
    {
        return [
            'AddAuthor' => [
                'relation' => 'authors',
                'initial' => [['external_id' => 'author_1', 'name' => 'John Doe']],
                'updated' => [
                    ['external_id' => 'author_1', 'name' => 'John Doe'],
                    ['external_id' => 'author_2', 'name' => 'Jane Smith'],
                ],
                'expectedNames' => ['John Doe', 'Jane Smith'],
            ],
            'RemoveAuthor' => [
                'relation' => 'authors',
                'initial' => [
                    ['external_id' => 'author_1', 'name' => 'John Doe'],
                    ['external_id' => 'author_2', 'name' => 'Jane Smith'],
                ],
                'updated' => [['external_id' => 'author_1', 'name' => 'John Doe']],
                'expectedNames' => ['John Doe'],
            ],
            'ReplaceAuthors' => [
                'relation' => 'authors',
                'initial' => [['external_id' => 'author_1', 'name' => 'John Doe']],
                'updated' => [['external_id' => 'author_2', 'name' => 'Jane Smith']],
                'expectedNames' => ['Jane Smith'],
            ],
            'AddSection' => [
                'relation' => 'sections',
                'initial' => [['external_id' => 'section_1', 'name' => 'Technology']],
                'updated' => [
                    ['external_id' => 'section_1', 'name' => 'Technology'],
                    ['external_id' => 'section_2', 'name' => 'Business'],
                ],
                'expectedNames' => ['Technology', 'Business'],
            ],
            'RemoveSection' => [
                'relation' => 'sections',
                'initial' => [
                    ['external_id' => 'section_1', 'name' => 'Technology'],
                    ['external_id' => 'section_2', 'name' => 'Business'],
                ],
                'updated' => [['external_id' => 'section_1', 'name' => 'Technology']],
                'expectedNames' => ['Technology'],
            ],
            'AddTag' => [
                'relation' => 'tags',
                'initial' => [['external_id' => 'tag_1', 'name' => 'PHP']],
                'updated' => [
                    ['external_id' => 'tag_1', 'name' => 'PHP'],
                    ['external_id' => 'tag_2', 'name' => 'Laravel'],
                ],
                'expectedNames' => ['PHP', 'Laravel'],
            ],
            'RemoveTag' => [
                'relation' => 'tags',
                'initial' => [
                    ['external_id' => 'tag_1', 'name' => 'PHP'],
                    ['external_id' => 'tag_2', 'name' => 'Laravel'],
                ],
                'updated' => [['external_id' => 'tag_1', 'name' => 'PHP']],
                'expectedNames' => ['PHP'],
            ],
        ];
    }

    #[DataProvider('relationOperationsDataProvider')]
    public function testRelationOperations(
        string $relation,
        array $initial,
        array $updated,
        array $expectedNames
    ): void {
        // Create with initial relations
        $this->upsertArticles([$this->makeArticlePayload([$relation => $initial])]);

        // Update with new relations
        $this->upsertArticles([$this->makeArticlePayload([$relation => $updated])]);

        $article = Article::where('external_id', 'article_1')->first();
        $this->assertCount(count($expectedNames), $article->$relation);
        $this->assertEqualsCanonicalizing($expectedNames, $article->$relation->pluck('name')->toArray());
    }

    public function testRemovedRelationsStillExistInDatabase(): void
    {
        // Create with relations
        $this->upsertArticles([
            $this->makeArticlePayload([
                'authors' => [['external_id' => 'author_1', 'name' => 'John Doe']],
                'sections' => [['external_id' => 'section_1', 'name' => 'Technology']],
                'tags' => [['external_id' => 'tag_1', 'name' => 'PHP']],
            ]),
        ]);

        // Remove all relations
        $this->upsertArticles([$this->makeArticlePayload()]);

        $article = Article::where('external_id', 'article_1')->first();
        $this->assertCount(0, $article->authors);
        $this->assertCount(0, $article->sections);
        $this->assertCount(0, $article->tags);

        // Entities still exist in database
        $this->assertDatabaseHas('authors', ['external_id' => 'author_1']);
        $this->assertDatabaseHas('sections', ['external_id' => 'section_1']);
        $this->assertDatabaseHas('tags', ['external_id' => 'tag_1']);
    }

    // =========================================================================
    // Tag Categories
    // =========================================================================

    public function testCreateArticleWithTagsAndTagCategories(): void
    {
        $response = $this->upsertArticles([
            $this->makeArticlePayload([
                'tags' => [
                    [
                        'external_id' => 'tag_1',
                        'name' => 'PHP',
                        'categories' => [
                            ['external_id' => 'cat_1', 'name' => 'Programming Languages'],
                            ['external_id' => 'cat_2', 'name' => 'Backend'],
                        ],
                    ],
                ],
            ]),
        ]);

        $response->assertSuccessful();

        $tag = Tag::where('external_id', 'tag_1')->first();
        $this->assertCount(2, $tag->tagCategories);
        $this->assertEqualsCanonicalizing(
            ['Programming Languages', 'Backend'],
            $tag->tagCategories->pluck('name')->toArray()
        );
    }

    public static function tagCategoryOperationsDataProvider(): array
    {
        return [
            'AddCategory' => [
                'initialCategories' => [['external_id' => 'cat_1', 'name' => 'Programming Languages']],
                'updatedCategories' => [
                    ['external_id' => 'cat_1', 'name' => 'Programming Languages'],
                    ['external_id' => 'cat_2', 'name' => 'Backend'],
                ],
                'expectedNames' => ['Programming Languages', 'Backend'],
            ],
            'RemoveCategory' => [
                'initialCategories' => [
                    ['external_id' => 'cat_1', 'name' => 'Programming Languages'],
                    ['external_id' => 'cat_2', 'name' => 'Backend'],
                ],
                'updatedCategories' => [['external_id' => 'cat_1', 'name' => 'Programming Languages']],
                'expectedNames' => ['Programming Languages'],
            ],
            'ReplaceCategories' => [
                'initialCategories' => [['external_id' => 'cat_1', 'name' => 'Programming Languages']],
                'updatedCategories' => [['external_id' => 'cat_2', 'name' => 'Backend']],
                'expectedNames' => ['Backend'],
            ],
        ];
    }

    #[DataProvider('tagCategoryOperationsDataProvider')]
    public function testTagCategoryOperations(
        array $initialCategories,
        array $updatedCategories,
        array $expectedNames
    ): void {
        $makeTagWithCategories = fn(array $categories) => [
            $this->makeArticlePayload([
                'tags' => [['external_id' => 'tag_1', 'name' => 'PHP', 'categories' => $categories]],
            ]),
        ];

        $this->upsertArticles($makeTagWithCategories($initialCategories));
        $this->upsertArticles($makeTagWithCategories($updatedCategories));

        $tag = Tag::where('external_id', 'tag_1')->first();
        $this->assertCount(count($expectedNames), $tag->tagCategories);
        $this->assertEqualsCanonicalizing($expectedNames, $tag->tagCategories->pluck('name')->toArray());
    }

    public function testRemovedTagCategoryStillExistsInDatabase(): void
    {
        $this->upsertArticles([
            $this->makeArticlePayload([
                'tags' => [
                    [
                        'external_id' => 'tag_1',
                        'name' => 'PHP',
                        'categories' => [
                            ['external_id' => 'cat_1', 'name' => 'Programming Languages'],
                            ['external_id' => 'cat_2', 'name' => 'Backend'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->upsertArticles([
            $this->makeArticlePayload([
                'tags' => [
                    [
                        'external_id' => 'tag_1',
                        'name' => 'PHP',
                        'categories' => [['external_id' => 'cat_1', 'name' => 'Programming Languages']],
                    ],
                ],
            ]),
        ]);

        $this->assertDatabaseHas('tag_categories', ['external_id' => 'cat_2', 'name' => 'Backend']);
    }

    public function testSharedTagAcrossArticlesWithDifferentCategories(): void
    {
        // First article with tag having category 1
        $this->upsertArticles([
            $this->makeArticlePayload([
                'tags' => [
                    [
                        'external_id' => 'tag_1',
                        'name' => 'PHP',
                        'categories' => [['external_id' => 'cat_1', 'name' => 'Programming Languages']],
                    ],
                ],
            ]),
        ]);

        // Second article with same tag but different category (overwrites tag's categories)
        $this->upsertArticles([
            $this->makeArticlePayload([
                'external_id' => 'article_2',
                'url' => 'https://example.com/article-2',
                'tags' => [
                    [
                        'external_id' => 'tag_1',
                        'name' => 'PHP',
                        'categories' => [['external_id' => 'cat_2', 'name' => 'Backend']],
                    ],
                ],
            ]),
        ]);

        $this->assertDatabaseCount('tags', 1);

        $tag = Tag::where('external_id', 'tag_1')->first();
        $this->assertCount(1, $tag->tagCategories);
        $this->assertEquals('Backend', $tag->tagCategories->first()->name);
    }

    // =========================================================================
    // Entity Name Updates
    // =========================================================================

    public static function entityNameUpdateDataProvider(): array
    {
        return [
            'Author' => [
                'relation' => 'authors',
                'table' => 'authors',
                'externalId' => 'author_1',
                'initialName' => 'John Doe',
                'updatedName' => 'John Smith',
            ],
            'Section' => [
                'relation' => 'sections',
                'table' => 'sections',
                'externalId' => 'section_1',
                'initialName' => 'Tech',
                'updatedName' => 'Technology',
            ],
            'Tag' => [
                'relation' => 'tags',
                'table' => 'tags',
                'externalId' => 'tag_1',
                'initialName' => 'php',
                'updatedName' => 'PHP',
            ],
        ];
    }

    #[DataProvider('entityNameUpdateDataProvider')]
    public function testEntityNameIsUpdated(
        string $relation,
        string $table,
        string $externalId,
        string $initialName,
        string $updatedName
    ): void {
        $makePayload = fn(string $name) => $this->makeArticlePayload([
            $relation => [['external_id' => $externalId, 'name' => $name]],
        ]);

        $this->upsertArticles([$makePayload($initialName)]);
        $this->upsertArticles([$makePayload($updatedName)]);

        $this->assertDatabaseCount($table, 1);
        $this->assertDatabaseHas($table, [
            'external_id' => $externalId,
            'name' => $updatedName,
        ]);
    }

    public function testTagCategoryNameIsUpdated(): void
    {
        $makePayload = fn(string $name) => $this->makeArticlePayload([
            'tags' => [
                [
                    'external_id' => 'tag_1',
                    'name' => 'PHP',
                    'categories' => [['external_id' => 'cat_1', 'name' => $name]],
                ],
            ],
        ]);

        $this->upsertArticles([$makePayload('Programming')]);
        $this->upsertArticles([$makePayload('Programming Languages')]);

        $this->assertDatabaseCount('tag_categories', 1);
        $this->assertDatabaseHas('tag_categories', [
            'external_id' => 'cat_1',
            'name' => 'Programming Languages',
        ]);
    }

    // =========================================================================
    // Existing Entity by Name (without external_id)
    // =========================================================================

    public static function existingEntityByNameDataProvider(): array
    {
        return [
            'Author' => [
                'modelClass' => Author::class,
                'relation' => 'authors',
                'table' => 'authors',
                'name' => 'John Doe',
                'externalId' => 'author_1',
            ],
            'Section' => [
                'modelClass' => Section::class,
                'relation' => 'sections',
                'table' => 'sections',
                'name' => 'Technology',
                'externalId' => 'section_1',
            ],
            'Tag' => [
                'modelClass' => Tag::class,
                'relation' => 'tags',
                'table' => 'tags',
                'name' => 'PHP',
                'externalId' => 'tag_1',
            ],
        ];
    }

    #[DataProvider('existingEntityByNameDataProvider')]
    public function testExistingEntityByNameWithoutExternalId(
        string $modelClass,
        string $relation,
        string $table,
        string $name,
        string $externalId
    ): void {
        // Pre-create entity without external_id
        $modelClass::create(['name' => $name]);

        // Upsert article with entity that has external_id
        $this->upsertArticles([
            $this->makeArticlePayload([
                $relation => [['external_id' => $externalId, 'name' => $name]],
            ]),
        ]);

        // Should update existing entity with external_id, not create new one
        $this->assertDatabaseCount($table, 1);
        $this->assertDatabaseHas($table, [
            'external_id' => $externalId,
            'name' => $name,
        ]);
    }

    // =========================================================================
    // No-op when unchanged (important for optimization verification)
    // =========================================================================

    public function testUpdateSameRelationsNoChange(): void
    {
        $payload = $this->makeArticlePayload([
            'authors' => [['external_id' => 'author_1', 'name' => 'John Doe']],
            'sections' => [['external_id' => 'section_1', 'name' => 'Technology']],
            'tags' => [
                [
                    'external_id' => 'tag_1',
                    'name' => 'PHP',
                    'categories' => [['external_id' => 'cat_1', 'name' => 'Programming Languages']],
                ],
            ],
        ]);

        $this->upsertArticles([$payload]);
        $response = $this->upsertArticles([$payload]);

        $response->assertSuccessful();

        $article = Article::where('external_id', 'article_1')->first();
        $this->assertCount(1, $article->authors);
        $this->assertCount(1, $article->sections);
        $this->assertCount(1, $article->tags);

        $tag = Tag::where('external_id', 'tag_1')->first();
        $this->assertCount(1, $tag->tagCategories);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function makeArticlePayload(array $overrides = []): array
    {
        return array_merge([
            'external_id' => 'article_1',
            'property_uuid' => 'test_property_uuid',
            'title' => 'Test Article',
            'url' => 'https://example.com/article-1',
            'published_at' => '2024-01-01T10:00:00Z',
        ], $overrides);
    }

    private function upsertArticles(array $articles): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('api.articles.upsert.v2'), ['articles' => $articles]);
    }
}
