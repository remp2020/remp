<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Remp\MailerModule\Models\PageMeta\Audio;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;

/**
 * Fetches an URL and builds a {@see Meta} from its JSON+LD (`application/ld+json`) article schema.
 *
 * Instances that need to tweak the result should extend it and override {@see self::processImage()}
 * or {@see self::processAuthors()}.
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

        return new Meta(
            $schema->headline ?? null,
            $schema->description ?? null,
            $this->processImage($this->extractImageUrl($schema->image ?? null)),
            $this->processAuthors($authors),
            $this->parseAudio($schema),
        );
    }

    /**
     * Hook for publisher-specific image URL rewriting. No-op by default.
     */
    protected function processImage(?string $image): ?string
    {
        return $image;
    }

    /**
     * Hook for publisher-specific author name formatting. No-op by default.
     */
    protected function processAuthors(array $authors): array
    {
        return $authors;
    }

    /**
     * Builds an {@see Audio} from the schema's `audio` property. Schema.org allows either a single
     * AudioObject or an array of them, only the first one is used.
     */
    protected function parseAudio(\stdClass $schema): ?Audio
    {
        $audio = $schema->audio ?? null;
        if (is_array($audio)) {
            $audio = $audio[0] ?? null;
        }
        if (!$audio instanceof \stdClass) {
            return null;
        }

        $url = $audio->contentUrl ?? null;
        if ($url === null) {
            // without a playable URL there's nothing a template could render
            return null;
        }

        // contentSize is Text in schema.org, publishers may emit "13.5 MB" instead of a byte count
        $size = $audio->contentSize ?? null;

        return new Audio(
            url: $url,
            size: is_numeric($size) ? (int) $size : null,
            durationInSeconds: $this->parseDurationInSeconds($audio->duration ?? null),
            thumbnailUrl: $this->extractImageUrl($audio->thumbnail ?? null),
        );
    }

    /**
     * Schema.org image properties can hold an ImageObject, an array of them, or a plain URL string.
     */
    protected function extractImageUrl(mixed $image): ?string
    {
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }
        if (is_string($image)) {
            return $image;
        }
        return $image->url ?? null;
    }

    /**
     * Converts an ISO-8601 duration (e.g. `PT0H14M4S`) to seconds. Returns null if unparseable.
     */
    protected function parseDurationInSeconds(?string $duration): ?int
    {
        if ($duration === null) {
            return null;
        }
        try {
            $interval = new \DateInterval($duration);
        } catch (\Exception $e) {
            return null;
        }
        return ((($interval->d * 24) + $interval->h) * 60 + $interval->i) * 60 + $interval->s;
    }
}
