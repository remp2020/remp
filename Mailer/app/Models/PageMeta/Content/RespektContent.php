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
        if (!$this->transport instanceof RespektApiTransport) {
            Debugger::log(self::class . ' depends on ' . RespektApiTransport::class . '.', ILogger::ERROR);
            return null;
        }

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
                $resolvedUrl = $this->transport->resolveRedirects($url);
                if (!$resolvedUrl) {
                    // The article wasn't published yet.
                    return null;
                }
                if ($url && $resolvedUrl !== $url) {
                    return $this->fetchUrlMeta($resolvedUrl);
                }

                return null;
            }
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }

        // get article title
        $title = $article['title'];

        // get article subtitle
        $subtitle = $this->getContentFromParts(
            parts: $article['subtitle']['parts'] ?? [],
            firstParagraphOnly: true,
        );

        // get first paragraph
        $firstContentParagraph = $this->getContentFromParts(
            parts: $article['content']['parts'],
            firstParagraphOnly: true,
        );

        // get first content part type
        $firstPart = Json::decode($article['content']['parts'][0]['json'], true);
        $firstContentPartType = $firstPart['children'][0]['type'];

        $fullContent = $this->getContentFromParts($article['content']['parts']);

        // get article cover image
        $image = $this->getImageUrl($article['coverPhoto']['image']['url']);

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
            fullContent: $fullContent,
        );
    }

    private function getContentFromParts(array $parts, bool $firstParagraphOnly = false): ?string
    {
        $processedContent = null;
        $references = [];

        $textChildTypes = [
            'paragraph' => true,
            'interTitle' => true,
        ];

        foreach ($parts as $contentPart) {
            try {
                // decode content
                $contentPartData = Json::decode($contentPart['json'], true);
            } catch (JsonException $e) {
                Debugger::log($e->getMessage(), ILogger::ERROR);
                return null;
            }

            // remap links so we can look by key (reference id)
            if (isset($contentPart['references'])) {
                foreach ($contentPart['references'] as $reference) {
                    $references[$reference['id']] = $reference;
                }
            }

            foreach ($contentPartData['children'] as $contentPartChild) {
                if ($textChildTypes[$contentPartChild['type']] ?? false) {
                    $processedPart = '<p>';
                    if ($contentPartChild['type'] === 'interTitle') {
                        $processedPart = '<strong>';
                    }

                    $processedChildren = '';
                    foreach ($contentPartChild['children'] as $child) {
                        $node = '';

                        if (isset($child['text'])) {
                            $node = $child['text'];
                            if (isset($child['isBold']) && $child['isBold'] === true) {
                                $node = "<strong>{$node}</strong>";
                            }
                            if (isset($child['isUnderlined']) && $child['isUnderlined'] === true) {
                                $node = "<u>{$node}</u>";
                            }
                            if (isset($child['isItalic']) && $child['isItalic'] === true) {
                                $node = "<em>{$node}</em>";
                            }
                        } elseif (isset($child['type']) && $child['type'] === 'link') {
                            $linkTarget = $references[$child['referenceId']]['target'];
                            $link = ($linkTarget['externalTarget'] ?? $linkTarget['internalTarget']) ?? null;
                            if ($link !== null) {
                                $node = $child['children'][0]['text']; // TODO[respekt#192]: this can contain multiple children with formatting
                                $node = "<a href='{$link}'>{$node}</a>";
                            }
                        }

                        $processedChildren .= $node;
                    }

                    if ($processedChildren) {
                        $processedPart .= $processedChildren;
                        if ($contentPartChild['type'] === 'interTitle') {
                            $processedPart .= '</strong>';
                        }
                        $processedContent .= $processedPart . '</p>';
                    }

                    if ($firstParagraphOnly) {
                        break 2;
                    }
                }

                if ($contentPartChild['type'] === 'reference' && isset($references[$contentPartChild['referenceId']])) {
                    $reference = $references[$contentPartChild['referenceId']];
                    if ($reference['type'] === 'image') {
                        $caption = $reference['image']['image']['title'];
                        if (!$caption && isset($reference['image']['image']['author']['name'])) {
                            $caption = 'Autor: ' . $reference['image']['image']['author']['name'];
                        }
                        $processedContent .= sprintf(
                            '<figure><img src="%s" alt="%s"><figcaption style="font-size: 0.8rem; color: #6b6b6b">%s</figcaption></figure>',
                            $this->getImageUrl($reference['image']['image']['url']),
                            $reference['image']['image']['author']['name'] ?? null,
                            $caption,
                        );
                    }
                }
            }
        }
        return $processedContent;
    }

    private function getImageUrl(string $sourceUrl): string
    {
        $image = preg_replace('#^https://#', '', $sourceUrl);
        return self::RESPEKT_IMAGE_URL . $image . '?width=500&fit=crop';
    }
}
