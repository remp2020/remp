<?php
declare(strict_types=1);

namespace Tests\Unit\Generator;

use Nette\Database\Table\Selection;
use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\Generators\GrafdnaGenerator;
use Remp\Mailer\Models\Generators\N3ArticleLocker;
use Remp\Mailer\Models\WebClient;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\EmbedParser;
use Remp\MailerModule\Models\Generators\WordpressHelpers;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

class GrafdnaGeneratorTest extends TestCase
{
    private const string IMAGE_URL = 'https://img.projektn.sk/og-image.png';

    private function process(string $html, array $posts = [], array $values = [], string $contentHtml = '{{ html|raw }}'): string
    {
        $sourceTemplateRepository = $this->createConfiguredStub(SourceTemplatesRepository::class, [
            'find' => new ActiveRow([
                'content_html' => $contentHtml,
                'content_text' => '{{ text|raw }}',
            ], $this->createStub(Selection::class)),
        ]);

        $snippets = $this->createStub(Selection::class);
        $snippets->method('where')->willReturnSelf();
        $snippets->method('fetch')->willReturn(null);

        $generator = new GrafdnaGenerator(
            $sourceTemplateRepository,
            new WordpressHelpers($this->createStub(ContentInterface::class)),
            $this->createStub(ContentInterface::class),
            $this->createStub(EmbedParser::class),
            new N3ArticleLocker(),
            $GLOBALS['container']->getByType(EngineFactory::class),
            $this->createConfiguredStub(WebClient::class, [
                'getEconomyPostsLast24Hours' => ['meta' => ['excerpt' => ''], 'posts' => $posts],
            ]),
            $this->createConfiguredStub(SnippetsRepository::class, ['all' => $snippets]),
            $this->createStub(TransportInterface::class),
        );

        return $generator->process($values + [
            'source_template_id' => 1,
            'grafdna_html' => $html,
            'image_url' => self::IMAGE_URL,
            'title' => 'Graf dňa',
            'url' => 'https://e.dennikn.sk/graf-dna/',
            'editor' => 'Editor',
        ])['htmlContent'];
    }

    private function embed(string $url, ?string $poster = null): string
    {
        $attributes = json_encode(array_filter(['url' => $url, 'poster' => $poster]), JSON_UNESCAPED_SLASHES);

        return <<<HTML
            <!-- wp:embed {$attributes} -->
            <figure class="wp-block-embed"><div class="wp-block-embed__wrapper">
            {$url}
            </div></figure>
            <!-- /wp:embed -->
            HTML;
    }

    public function testEveryEmbedWithPosterUsesItAndLinksToEmbed(): void
    {
        $html = $this->embed('https://public.flourish.studio/visualisation/1/', 'https://public.flourish.studio/visualisation/1/thumbnail')
            . "\n\n" . $this->embed('https://example.com/chart/1/', 'https://example.com/chart/1/thumbnail');

        $output = $this->process($html);

        self::assertStringNotContainsString(self::IMAGE_URL, $output);
        self::assertStringContainsString('src="https://public.flourish.studio/visualisation/1/thumbnail"', $output);
        self::assertStringContainsString('src="https://example.com/chart/1/thumbnail"', $output);
        self::assertStringContainsString('Graf nájdete aj na <a href="https://public.flourish.studio/visualisation/1/"', $output);
        self::assertStringContainsString('Graf nájdete aj na <a href="https://example.com/chart/1/"', $output);
    }

    public function testEmbedWithoutPosterHasNoGraphLink(): void
    {
        $output = $this->process($this->embed('https://datawrapper.dwcdn.net/abc/1/'));

        self::assertStringNotContainsString('<img', $output);
        self::assertStringNotContainsString('Graf nájdete aj na', $output);
    }

    private function post(int $id, array $tagSlugs = []): array
    {
        return [
            'id' => $id,
            'url' => "https://e.dennikn.sk/{$id}/clanok-{$id}/",
            'tags' => array_map(static fn (string $slug) => ['slug' => $slug], $tagSlugs),
            'image' => ['sizes' => [['width' => 720, 'height' => 480, 'url' => "https://img.dennikn.sk/{$id}.jpg"]]],
        ];
    }

    private function renderedPostIds(array $values): string
    {
        $posts = [$this->post(1), $this->post(2), $this->post(3, ['graf-dna']), $this->post(4, ['ekonomicky-newsfilter'])];

        return $this->process('', $posts, $values, '{% for post in economyPosts %}[{{ post.id }}]{% endfor %}');
    }

    public function testEconomyPostsExcludeOwnArticleById(): void
    {
        self::assertSame('[2]', $this->renderedPostIds(['article_id' => '1']));
    }

    public function testEconomyPostsExcludeOwnArticleByUrlWithoutArticleId(): void
    {
        self::assertSame('[2]', $this->renderedPostIds(['url' => 'https://dennikn.sk/1/clanok-1/']));
    }

    public function testEconomyPostsKeepAllUntaggedWhenOwnArticleUnknown(): void
    {
        self::assertSame('[1][2]', $this->renderedPostIds([]));
    }
}
