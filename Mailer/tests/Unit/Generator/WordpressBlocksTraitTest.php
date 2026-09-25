<?php
declare(strict_types=1);

namespace Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\Generators\WordpressBlocksTrait;

class WordpressBlocksTraitTest extends TestCase
{
    private object $preprocessor;

    protected function setUp(): void
    {
        $this->preprocessor = new class {
            use WordpressBlocksTrait {
                preprocessBlocks as public;
            }
        };
    }

    public function testEmbedWithPosterIsReplacedByPoster(): void
    {
        $post = <<<HTML
<!-- wp:embed {"url":"https://public.flourish.studio/visualisation/30337987/","type":"rich","providerNameSlug":"flourish","responsive":true,"poster":"https://public.flourish.studio/visualisation/30337987/thumbnail","className":"wp-embed-aspect-1-1 wp-has-aspect-ratio"} -->
<figure class="wp-block-embed is-type-rich is-provider-flourish wp-block-embed-flourish wp-embed-aspect-1-1 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
https://public.flourish.studio/visualisation/30337987/
</div></figure>
<!-- /wp:embed -->
HTML;

        $result = $this->preprocessor->preprocessBlocks($post);

        $this->assertSame(
            '<a href="https://public.flourish.studio/visualisation/30337987/">'
                . '<img src="https://public.flourish.studio/visualisation/30337987/thumbnail" alt="" /></a>',
            trim($result)
        );
    }

    public function testEmbedWithoutPosterIsLeftAsBareUrl(): void
    {
        $post = <<<HTML
<!-- wp:embed {"url":"https://www.youtube.com/watch?v=abc","type":"video","providerNameSlug":"youtube"} -->
<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">
https://www.youtube.com/watch?v=abc
</div></figure>
<!-- /wp:embed -->
HTML;

        $result = $this->preprocessor->preprocessBlocks($post);

        $this->assertSame('https://www.youtube.com/watch?v=abc', trim($result));
    }

    public function testPosterIsEscaped(): void
    {
        // WordPress serializes block attributes with `&` and `"` as unicode escapes.
        $attributes = json_encode(
            ['url' => 'https://example.com/chart?a=1&b=2', 'poster' => 'https://example.com/thumb?x="y"&z=1'],
            JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        );
        $post = <<<HTML
<!-- wp:embed {$attributes} -->
<figure class="wp-block-embed"><div class="wp-block-embed__wrapper">
https://example.com/chart?a=1&b=2
</div></figure>
<!-- /wp:embed -->
HTML;

        $result = $this->preprocessor->preprocessBlocks($post);

        $this->assertSame(
            '<a href="https://example.com/chart?a=1&amp;b=2">'
                . '<img src="https://example.com/thumb?x=&quot;y&quot;&amp;z=1" alt="" /></a>',
            trim($result)
        );
    }
}
