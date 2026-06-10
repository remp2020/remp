<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;

/**
 * Fetches an URL and builds a {@see Meta} from its JSON+LD (`application/ld+json`) article schema.
 *
 * Instances that need to tweak the result (e.g. rewrite image URLs or format author names)
 * should extend it and override {@see self::postProcessMeta()}.
 */
class JsonLDContent implements ContentInterface
{
    use JsonLDSchemaTrait;

    public function __construct(
        protected TransportInterface $transport,
    ) {
    }

    public function fetchUrlMeta(string $url): ?Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $content = $this->transport->getContent($url);
            if ($content === null) {
                return null;
            }
            return $this->parseMeta($content);
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
    }

    public function parseMeta(string $content): ?Meta
    {
        $schema = $this->extractSchema($content);
        if ($schema === null) {
            return null;
        }

        $authors = [];
        $schemaAuthors = $schema->author ?? [];
        if (!is_array($schemaAuthors)) {
            $schemaAuthors = [$schemaAuthors];
        }
        foreach ($schemaAuthors as $author) {
            $authors[] = $author->name;
        }

        $image = null;
        if (isset($schema->image)) {
            $image = is_array($schema->image)
                ? ($schema->image[0]->url ?? null)
                : ($schema->image->url ?? null);
        }

        $meta = new Meta($schema->headline ?? null, $schema->description ?? null, $image, $authors);
        return $this->postProcessMeta($meta);
    }

    /**
     * Hook for publisher-specific adjustments (image URL rewriting, author formatting, ...).
     * No-op by default. Meta is immutable, so overrides return a rebuilt instance.
     */
    protected function postProcessMeta(Meta $meta): Meta
    {
        return $meta;
    }
}
