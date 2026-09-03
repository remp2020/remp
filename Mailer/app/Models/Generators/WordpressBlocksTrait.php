<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

/**
 * Translates WordPress block-editor markup into the classic constructs {@see RulesTrait}.
 *
 * The block editor keeps its structure in HTML comments (`<!-- wp:paragraph -->`), and RulesTrait's
 * patterns are class-tolerant (`<p.*?>`, `<h2.*?>`, so most of a block post already survives the rules
 * pass unchanged.
 *
 * Content from the classic editor carries no block comments and is returned untouched, so both
 * editors keep working from the same code path.
 */
trait WordpressBlocksTrait
{
    protected function preprocessBlocks(string $post): string
    {
        if (!preg_match('/<!--\s+wp:/', $post)) {
            return $post;
        }

        $post = $this->convertBlockLocks($post);
        $post = $this->convertBlockArticleLinks($post);
        $post = $this->dropPullQuoteBlocks($post);
        $post = $this->convertBoxBlocks($post);
        $post = $this->convertBlockEmbeds($post);
        $post = $this->convertBlockImages($post);

        // The <hr> rule matches the literal tag only, so drop the separator block's classes.
        $post = preg_replace('/<hr\b[^>]*>/i', '<hr />', $post);

        $post = $this->stripBlockComments($post);

        // <figure> and <figcaption> are matched by no rule. Left in place they reach the mail with
        // the client's own styling — an indented figure and an 18px caption.
        $post = preg_replace('/<\/?figure\b[^>]*>/i', '', $post);
        $post = preg_replace(
            '/<figcaption\b[^>]*>(.*?)<\/figcaption>/is',
            '<p><small style="font-size:13px;line-height:18px;display:block;color:#9B9B9B;">$1</small></p>',
            $post
        );

        // [gallery] carries attachment ids rather than URLs, so it cannot be resolved outside WordPress.
        $post = preg_replace('/\[gallery\b[^\]]*\]/i', '', $post);

        // Empty paragraphs are the block editor's spacers.
        $post = preg_replace('/<p\b[^>]*>\s*<\/p>/i', '', $post);

        // Consuming delimiters leaves runs of blank lines behind; wpautop() only cares that there is
        // at least one, and the text variant reads better without them.
        $post = preg_replace('/\n{3,}/', "\n\n", $post);

        return $post;
    }

    /**
     * A lock is a marker, not a container: the delimiter, an empty `<div id="p_lock__…">`, then the
     * closing delimiter, and everything after it in the document is gated.
     */
    private function convertBlockLocks(string $post): string
    {
        return preg_replace_callback(
            '/<!--\s*wp:[a-z0-9-]+\/lock(?:\s+(\{.*?\}))?\s*\/?-->'
                . '\s*(?:<div\b[^>]*>\s*<\/div>\s*)?'
                . '(?:<!--\s*\/wp:[a-z0-9-]+\/lock\s*-->)?/is',
            static function (array $matches): string {
                // `hard` is the default type, and the editor omits attributes left at their default,
                // so a bare `<!-- wp:nn/lock -->` is a hard lock rather than an unknown one.
                $type = 'hard';
                if (($matches[1] ?? '') !== '') {
                    $attributes = json_decode($matches[1], true);
                    if (is_array($attributes)) {
                        $type = (string) ($attributes['type'] ?? 'hard');
                    }
                }

                return match (strtolower($type)) {
                    'newsletter' => "\n\n[lock newsletter]\n\n",
                    'hard' => "\n\n[lock]\n\n",
                    // `email`, `e` and `club` do not cut a newsletter today either.
                    default => '',
                };
            },
            $post
        );
    }

    private function convertBlockArticleLinks(string $post): string
    {
        return preg_replace_callback(
            '/<!--\s*wp:[a-z0-9-]+\/link\s+(\{.*?\})\s*\/-->/is',
            static function (array $matches): string {
                $attributes = json_decode($matches[1], true);
                $id = is_array($attributes) ? ($attributes['postId'] ?? $attributes['id'] ?? null) : null;

                return $id ? "\n\n[articlelink id=\"{$id}\"]\n\n" : '';
            },
            $post
        );
    }

    /**
     * A pull quote repeats a sentence that is already in the body, so a newsletter carrying both
     * prints it twice.
     */
    private function dropPullQuoteBlocks(string $post): string
    {
        return preg_replace(
            '/<!--\s*wp:[a-z0-9-]+\/pull(?:\s+\{.*?\})?\s*-->.*?<!--\s*\/wp:[a-z0-9-]+\/pull\s*-->/is',
            '',
            $post
        );
    }

    /**
     * Boxes become the `t_greybox` div that classic content uses, so a box written in either editor
     * renders through the same greybox template.
     */
    private function convertBoxBlocks(string $post): string
    {
        $post = preg_replace(
            '/<!--\s*wp:[a-z0-9-]+\/box(?:\s+\{.*?\})?\s*-->\s*<div\b[^>]*>/is',
            '<div class="t_greybox">',
            $post
        );

        return preg_replace('/<\/div>\s*<!--\s*\/wp:[a-z0-9-]+\/box\s*-->/is', '</div>', $post);
    }

    private function convertBlockEmbeds(string $post): string
    {
        return preg_replace_callback(
            '/<figure\b[^>]*class="[^"]*wp-block-embed[^"]*"[^>]*>.*?'
                . '<div\b[^>]*class="[^"]*wp-block-embed__wrapper[^"]*"[^>]*>\s*(https?:\/\/\S+)\s*<\/div>'
                . '.*?<\/figure>/is',
            static fn(array $matches): string => "\n\n" . trim($matches[1]) . "\n\n",
            $post
        );
    }

    private function convertBlockImages(string $post): string
    {
        return preg_replace_callback(
            '/<figure\b[^>]*class="[^"]*wp-block-image[^"]*"[^>]*>\s*(?:<a\b[^>]*>\s*)?(<img[^>]*(?:\/>|>))'
                . '\s*(?:<\/a>\s*)?(?:<figcaption[^>]*>(.*?)<\/figcaption>)?\s*<\/figure>/is',
            static function (array $matches): string {
                preg_match('/src="([^"]+)"/', $matches[1], $src);
                $image = '<img src="' . ($src[1] ?? '') . '" alt="" />';

                $caption = isset($matches[2]) ? trim(strip_tags($matches[2])) : '';
                if ($caption === '') {
                    return $image;
                }

                $caption = htmlspecialchars(
                    html_entity_decode($caption, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    ENT_QUOTES
                );

                return '[caption]' . $image . $caption . '[/caption]';
            },
            $post
        );
    }

    /**
     * The alternation is there because block attributes carry author-supplied strings, and those may
     * contain both `>` and `-->`. Scanning quoted strings as a unit means neither terminates the
     * delimiter early and leaves half of it in the mail.
     */
    private function stripBlockComments(string $post): string
    {
        return preg_replace('/<!--\s*\/?wp:(?:"(?:\\\\.|[^"\\\\])*"|[^">])*-->/s', '', $post);
    }
}
