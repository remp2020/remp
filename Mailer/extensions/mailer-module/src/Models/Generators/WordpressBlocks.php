<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

/**
 * Works with blocks of the WordPress block editor, which are serialized as HTML comments:
 *
 *   <!-- wp:embed {"url":"https://..."} --> rendered HTML <!-- /wp:embed -->
 *   <!-- wp:eo/link {"id":210923} /-->
 */
class WordpressBlocks
{
    /**
     * Replaces every block called $name with whatever $callback returns.
     *
     * $name is the block name without the `wp:` prefix, e.g. `embed` or `nn/lock`. `*` as the namespace
     * matches any namespace, so `nn/lock`, `eo/lock`, … are all matched by the same call.
     *
     * Blocks of the same name nested in each other are not supported.
     *
     * @param callable(array $attributes, string $innerHtml): ?string $callback
     *   $attributes are the decoded JSON attributes (empty if the block has none), $innerHtml is the
     *   rendered HTML between the delimiters (empty for self-closing blocks). Returning null leaves
     *   the block untouched.
     */
    public static function replace(string $html, string $name, callable $callback): string
    {
        $name = str_replace('\*', '[a-z0-9-]+', preg_quote($name, '/'));

        // WordPress escapes `--`, `<` and `>` inside attributes, so the JSON can never contain `-->`.
        $pattern = '/<!--\s*wp:(' . $name . ')(?:\s+(\{.*?\}))?\s*'
            . '(?:\/-->|-->(.*?)<!--\s*\/wp:\1\s*-->)/is';

        return preg_replace_callback(
            $pattern,
            static function (array $matches) use ($callback): string {
                $attributes = json_decode($matches[2] ?? '', true);
                $innerHtml = $matches[3] ?? '';

                $replacement = $callback(is_array($attributes) ? $attributes : [], $innerHtml);
                if ($replacement !== null) {
                    return $replacement;
                }

                return $matches[0];
            },
            $html
        );
    }
}
