<?php
declare(strict_types=1);

namespace Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\Generators\WordpressBlocks;

class WordpressBlocksTest extends TestCase
{
    public function testBlockWithAttributesAndInnerHtml(): void
    {
        $html = '<!-- wp:embed {"url":"https://example.com/","poster":"https://example.com/p.png"} -->'
            . '<figure>https://example.com/</figure>'
            . '<!-- /wp:embed -->';

        $result = WordpressBlocks::replace($html, 'embed', function (array $attributes, string $innerHtml): string {
            $this->assertSame(['url' => 'https://example.com/', 'poster' => 'https://example.com/p.png'], $attributes);
            $this->assertSame('<figure>https://example.com/</figure>', $innerHtml);
            return 'REPLACED';
        });

        $this->assertSame('REPLACED', $result);
    }

    public function testSelfClosingBlock(): void
    {
        $html = 'before <!-- wp:eo/link {"id":210923} /--> after';

        $result = WordpressBlocks::replace($html, 'eo/link', function (array $attributes, string $innerHtml): string {
            $this->assertSame(['id' => 210923], $attributes);
            $this->assertSame('', $innerHtml);
            return 'LINK';
        });

        $this->assertSame('before LINK after', $result);
    }

    public function testBlockWithoutAttributes(): void
    {
        $html = '<!-- wp:nn/lock --><div></div><!-- /wp:nn/lock -->';

        $result = WordpressBlocks::replace($html, 'nn/lock', function (array $attributes): string {
            $this->assertSame([], $attributes);
            return 'LOCK';
        });

        $this->assertSame('LOCK', $result);
    }

    public function testNestedAttributes(): void
    {
        $html = '<!-- wp:heading {"level":3,"style":{"color":{"text":"red"}}} --><h3>A</h3><!-- /wp:heading -->';

        $result = WordpressBlocks::replace($html, 'heading', function (array $attributes): string {
            $this->assertSame(['level' => 3, 'style' => ['color' => ['text' => 'red']]], $attributes);
            return 'HEADING';
        });

        $this->assertSame('HEADING', $result);
    }

    public function testAnyNamespace(): void
    {
        $html = '<!-- wp:nn/lock {"type":"e"} --><!-- /wp:nn/lock -->|<!-- wp:eo/lock /-->';

        $result = WordpressBlocks::replace($html, '*/lock', static fn(array $attributes): string => $attributes['type'] ?? 'hard');

        $this->assertSame('e|hard', $result);
    }

    public function testOtherBlocksAreLeftUntouched(): void
    {
        $html = '<!-- wp:paragraph --><p>A</p><!-- /wp:paragraph -->'
            . '<!-- wp:embed {"url":"https://example.com/"} --><figure></figure><!-- /wp:embed -->'
            . '<!-- wp:paragraph --><p>B</p><!-- /wp:paragraph -->';

        $result = WordpressBlocks::replace($html, 'embed', static fn(): string => 'EMBED');

        $this->assertSame(
            '<!-- wp:paragraph --><p>A</p><!-- /wp:paragraph -->'
                . 'EMBED'
                . '<!-- wp:paragraph --><p>B</p><!-- /wp:paragraph -->',
            $result
        );
    }

    public function testNullLeavesBlockUntouched(): void
    {
        $html = '<!-- wp:embed {"url":"https://a.example/"} --><figure></figure><!-- /wp:embed -->'
            . '<!-- wp:embed {"url":"https://b.example/"} --><figure></figure><!-- /wp:embed -->';

        $result = WordpressBlocks::replace(
            $html,
            'embed',
            static fn(array $attributes): ?string => $attributes['url'] === 'https://b.example/' ? 'B' : null
        );

        $this->assertSame('<!-- wp:embed {"url":"https://a.example/"} --><figure></figure><!-- /wp:embed -->B', $result);
    }

    public function testEmptyStringRemovesBlock(): void
    {
        $html = 'before <!-- wp:nn/pull --><blockquote>Q</blockquote><!-- /wp:nn/pull --> after';

        $result = WordpressBlocks::replace($html, 'nn/pull', static fn(): string => '');

        $this->assertSame('before  after', $result);
    }
}
