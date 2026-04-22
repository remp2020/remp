<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;

class EuobserverContent implements ContentInterface
{
    private $transport;

    public function __construct(
        TransportInterface $transport
    ) {
        $this->transport = $transport;
    }

    public function fetchUrlMeta(string $url): ?Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $content = $this->transport->getContent($url);
            if ($content === null) {
                return null;
            }

            $meta = $this->parseMeta($content);
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
        return $meta;
    }

    public function parseMeta(string $content): ?Meta
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#', $content, $matches);

        if (!$matches || empty($matches[1])) {
            return null;
        }

        try {
            $schema = Json::decode($matches[1][0]);
        } catch (JsonException $e) {
            return null;
        }

        // author
        $authors = [];
        if (isset($schema->author) && !is_array($schema->author)) {
            $schema->author = [$schema->author];
        }
        foreach ($schema->author ?? [] as $author) {
            $authors[] = Strings::upper($author->name);
        }

        $title = $schema->headline ?? null;
        $description = $schema->description ?? null;
        $image = $schema->image?->url;

        return new Meta($title, $description, $image, $authors);
    }
}
