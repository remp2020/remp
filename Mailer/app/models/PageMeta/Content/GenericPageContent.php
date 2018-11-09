<?php

namespace Remp\MailerModule\PageMeta;

use GuzzleHttp\Exception\RequestException;

class GenericPageContent implements ContentInterface
{
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function fetchUrlMeta($url): ?Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $content = $this->transport->getContent($url);
            if (!$content) {
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

    public function parseMeta($content)
    {
        // author
        $authors = false;
        $matches = [];
        preg_match_all('/<meta name=\"author\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            foreach ($matches[1] as $author) {
                $authors[] = html_entity_decode($author);
            }
        }

        // title
        $title = false;
        $matches = [];
        preg_match('/<meta property=\"og:title\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            $title = html_entity_decode($matches[1]);
        }

        // description
        $description = false;
        $matches = [];
        preg_match('/<meta property=\"og:description\" content=\"(.*)\">/Us', $content, $matches);
        if ($matches) {
            $description = html_entity_decode($matches[1]);
        }

        // image
        $image = false;
        $matches = [];
        preg_match('/<meta property=\"og:image\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            $image = $matches[1];
        }

        return new Meta($title, $description, $image, $authors);
    }
}
