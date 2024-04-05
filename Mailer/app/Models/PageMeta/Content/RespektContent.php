<?php

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\Mailer\Models\PageMeta\RespektMeta;
use Remp\Mailer\Models\PageMeta\Transport\RespektApiTransport;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class RespektContent implements ContentInterface
{
    private const RESPEKT_IMAGE_URL = 'https://i.respekt.cz/';

    private array $categoryArticleTypeMap = [
        '63d5958a-cb51-4aee-b6a2-81f560ce4f6d' => 'podcast',
        '65357154-6820-441d-ae1d-a7a9b456e68f' => 'commentary',
        'd8c0a494-2241-4a27-ab75-d64cdbae8007' => 'interview',
    ];

    public function __construct(private TransportInterface $transport)
    {
    }

    public function fetchUrlMeta(string $url): ?Meta
    {
        try {
            $data = $this->transport->getContent($url);
            if ($data === null) {
                return null;
            }

            try {
                $data = Json::decode($data, true);
            } catch (JsonException $e) {
                Debugger::log($e->getMessage(), ILogger::ERROR);
                return null;
            }

            $article = $data['data']['getArticle'];
            if ($article === null) {
                // URL has changed, we need to follow redirects and determine new URL
                if ($this->transport instanceof RespektApiTransport) {
                    $resolvedUrl = $this->transport->resolveRedirects($url);
                    if ($url && $resolvedUrl !== $url) {
                        return $this->fetchUrlMeta($resolvedUrl);
                    }
                }

                return null;
            }
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }

        // get article title
        $title = $article['title'];

        // get article subtitle
        $subtitle = $this->getFirstParagraphFromParts($article['subtitle']['parts']);

        // get first paragraph
        $firstContentParagraph = $this->getFirstParagraphFromParts($article['content']['parts']);

        // get first content part type
        $firstPart = Json::decode($article['content']['parts'][0]['json'], true);
        $firstContentPartType = $firstPart['children'][0]['type'];

        // get article cover image
        $image = $article['coverPhoto']['image']['url'];
        $image = ltrim($image, 'https://');
        $image = self::RESPEKT_IMAGE_URL . $image . '?width=500&fit=crop';

        // get article authors
        $authors = [];
        foreach ($article['authors'] as $author) {
            $authors[] = $author['author']['name'];
        }

        // get article type
        $articleType = null;
        foreach ($article['categories'] as $articleCategory) {
            $category = $articleCategory['category'];
            if (array_key_exists($category['id'], $this->categoryArticleTypeMap)) {
                $articleType = $this->categoryArticleTypeMap[$category['id']];
                break;
            }
        }

        return new RespektMeta(
            title: $title,
            image: $image,
            authors: $authors,
            type: $articleType,
            subtitle: $subtitle,
            firstParagraph: $firstContentParagraph,
            firstContentPartType: $firstContentPartType,
        );
    }

    private function getFirstParagraphFromParts($parts): ?string
    {
        $description = null;
        foreach ($parts as $contentPart) {
            try {
                $contentPartData = Json::decode($contentPart['json'], true);
            } catch (JsonException $e) {
                Debugger::log($e->getMessage(), ILogger::ERROR);
                return null;
            }

            foreach ($contentPartData['children'] as $contentPartChild) {
                if ($contentPartChild['type'] === 'paragraph') {
                    foreach ($contentPartChild['children'] as $child) {
                        $text = trim($child['text']);
                        if ($text) {
                            $description = $text;
                            break 3;
                        }
                    }
                }
            }
        }
        return $description;
    }
}
