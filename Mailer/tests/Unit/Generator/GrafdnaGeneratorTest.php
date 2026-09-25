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

    private function process(string $html): string
    {
        $sourceTemplateRepository = $this->createConfiguredStub(SourceTemplatesRepository::class, [
            'find' => new ActiveRow([
                'content_html' => '{{ html|raw }}',
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
                'getEconomyPostsLast24Hours' => ['meta' => ['excerpt' => ''], 'posts' => []],
            ]),
            $this->createConfiguredStub(SnippetsRepository::class, ['all' => $snippets]),
            $this->createStub(TransportInterface::class),
        );

        return $generator->process([
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

    public function testFirstGraphIsReplacedByImageEvenIfItHasPoster(): void
    {
        $html = $this->embed('https://public.flourish.studio/visualisation/1/', 'https://public.flourish.studio/visualisation/1/thumbnail')
            . "\n\n" . $this->embed('https://public.flourish.studio/visualisation/2/', 'https://public.flourish.studio/visualisation/2/thumbnail');

        $output = $this->process($html);

        self::assertStringContainsString('src="' . self::IMAGE_URL . '"', $output);
        self::assertStringContainsString('href="' . self::IMAGE_URL . '"', $output);
        self::assertStringContainsString('Graf nájdete aj na', $output);
        self::assertStringNotContainsString('visualisation/1/thumbnail', $output);
        // Only the first graph gets the image, the second one falls back to its poster.
        self::assertStringContainsString('src="https://public.flourish.studio/visualisation/2/thumbnail"', $output);
        self::assertSame(1, substr_count($output, 'src="' . self::IMAGE_URL . '"'));
    }

    public function testNonGraphEmbedIsNotReplaced(): void
    {
        $html = $this->embed('https://www.youtube.com/watch?v=abc')
            . "\n\n" . $this->embed('https://datawrapper.dwcdn.net/abc/1/');

        $output = $this->process($html);

        self::assertSame(1, substr_count($output, 'src="' . self::IMAGE_URL . '"'));
        self::assertStringContainsString('href="https://datawrapper.dwcdn.net/abc/1/"', $output);
    }
}
