<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;

class TyzdenContent implements ContentInterface
{
    private $transport;

    public function __construct(TransportInterface $transport)
    {
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
            if (!$meta) {
                return null;
            }
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
        return $meta;
    }

    public function parseMeta(string $content): Meta
    {
        // author
        $authors = [];
        $matches = [];
        preg_match_all('/<meta name=\"author\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            foreach ($matches[1] as $author) {
                $authors[] = $author;
            }
        }

        // title
        $title = null;
        $matches = [];
        preg_match('/<meta property=\"og:title\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            $title = $matches[1];
        }

        // description
        $description = null;
        $matches = [];
        preg_match('/<meta property=\"og:description\" content=\"(.+)\">/Us', $content, $matches);
        if ($matches) {
            $description = $matches[1];
        }

        // image
        $image = null;
        $matches = [];
        preg_match('/<meta property=\"og:image\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            $image = $matches[1];
        }

        return new Meta($title, $description, $image, $authors);
    }
}
