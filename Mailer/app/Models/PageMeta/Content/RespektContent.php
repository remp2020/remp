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
    private const RESPEKT_PAGE_URL = 'https://respekt.cz/';

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
            countOfParagraphs: 1,
        );

        // get first paragraph
        $firstContentParagraph = $this->getContentFromParts(
            parts: $article['content']['parts'],
            countOfParagraphs: 1,
        );

        // get first content part type
        $firstPart = Json::decode($article['content']['parts'][0]['json'], true);
        $firstContentPartType = $firstPart['children'][0]['type'];

        $fullContent = $this->getContentFromParts($article['content']['parts']);
        $unLockedContent = $this->getContentFromParts($article['content']['parts'], 3);

        // get article cover image
        $image = null;
        if (isset($article['coverPhoto']['image']['url'])) {
            $image = $this->getImageUrl($article['coverPhoto']['image']['url']);
        }
        $imageTitle = $article['coverPhoto']['title'] ?? $article['coverPhoto']['image']['title'];
        $imageAuthor = $article['coverPhoto']['image']['author']['name'] ?? null;
        if ($imageAuthor) {
            $imageTitle .= " &bull; Autor: {$imageAuthor}";
        }

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

        // get newsletter subject
        $subject = null;
        if (isset($article['newsletterSubject'])) {
            $subject = $article['newsletterSubject'];
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
            unlockedContent: $unLockedContent,
            imageTitle: $imageTitle,
            subject: $subject,
        );
    }

    private function getContentFromParts(array $parts, int $countOfParagraphs = null): ?string
    {
        $paragraphCount = 0;
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
                            if (isset($child['isStruckThrough']) && $child['isStruckThrough'] === true) {
                                $node = "<s>{$node}</s>";
                            }
                        } elseif (isset($child['type']) && $child['type'] === 'link') {
                            $linkTarget = $references[$child['referenceId']]['target'];
                            $link = ($linkTarget['externalTarget'] ?? $linkTarget['internalTarget']) ?? null;
                            if ($link !== null) {
                                $node = $child['children'][0]['text']; // TODO[respekt#192]: this can contain multiple children with formatting

                                $node = is_array($link) ? "<a href='{$this->getAbsoluteUrl($link['url'])}'>{$node}</a>" : "<a href='{$this->getAbsoluteUrl($link)}'>{$node}</a>";
                            }
                        } elseif (isset($child['type']) && $child['type'] === 'anchor') {
                            $node = $child['children'][0]['text'];
                            $node = "<a href='{$child['href']}'>{$node}</a>";
                        }

                        $processedChildren .= $node;
                    }

                    if ($processedChildren) {
                        $processedPart .= $processedChildren;
                        if ($contentPartChild['type'] === 'interTitle') {
                            $processedPart .= '</strong>';
                        }
                        $processedContent .= $processedPart . '</p>';
                        $paragraphCount++;
                    }

                    if ($countOfParagraphs && $countOfParagraphs === $paragraphCount) {
                        break 2;
                    }
                }

                if ($contentPartChild['type'] === 'reference' && isset($references[$contentPartChild['referenceId']])) {
                    $reference = $references[$contentPartChild['referenceId']];
                    if ($reference['type'] === 'image' && $reference['image']['image']['url']) {
                        $title = $reference['image']['image']['title'];
                        if (isset($reference['image']['title'])) {
                            $title = $reference['image']['title'];
                        }
                        $author = null;
                        if (isset($reference['image']['image']['author']['name'])) {
                            $author = 'Autor: ' . $reference['image']['image']['author']['name'];
                        }
                        $processedContent .= sprintf(
                            '<figure><img src="%s" alt="%s"><figcaption style="font-size: 0.8rem; color: #6b6b6b; font-style: italic;">%s</figcaption></figure><p></p>',
                            $this->getImageUrl($reference['image']['image']['url']),
                            $author,
                            isset($title, $author) ? "{$title} &bull; {$author}" : "",
                        );
                    }

                    if ($reference['type'] === 'block_link' && isset($reference['target']['internalTarget']['article']['title'])) {
                        $processedContent .= "<p><strong>Mohlo by vás zaujmout:</strong> <a href='{$this->getAbsoluteUrl($reference['target']['internalTarget']['url'])}'>{$reference['target']['internalTarget']['article']['title']}</a></p>";
                    }

                    if ($reference['type'] === 'horizontal_divider') {
                        $processedContent .= '<hr>';
                    }
                }

                if ($contentPartChild['type'] === 'question' && isset($contentPartChild['children'][0]['text'])) {
                    $processedContent .= "<p><strong>{$contentPartChild['children'][0]['text']}</strong></p>";
                }

                if ($contentPartChild['type'] === 'heading') {
                    $level = $contentPartChild['level'];
                    $fontSize = match ($level) {
                        1 => 32,
                        2 => 24,
                        3 => 18,
                        default => 20
                    };
                    $processedContent .= "<h{$level} style='font-size: {$fontSize}px;'>{$contentPartChild['children'][0]['text']}</h{$level}>";
                }

                if ($contentPartChild['type'] === 'unorderedList') {
                    $processedContent .= "<ul>";
                    foreach ($contentPartChild['children'] as $listItem) {
                        $processedContent .= "<li>{$listItem['children'][0]['text']}</li>";
                    }
                    $processedContent .= "</ul>";
                }

                if ($contentPartChild['type'] === 'orderedList') {
                    $processedContent .= "<ol>";
                    foreach ($contentPartChild['children'] as $listItem) {
                        $processedContent .= "<li>{$listItem['children'][0]['text']}</li>";
                    }
                    $processedContent .= "</ol>";
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

    private function getAbsoluteUrl(string $sourceUrl): string
    {
        if (str_starts_with($sourceUrl, 'https://')) {
            return $sourceUrl;
        }
        return self::RESPEKT_PAGE_URL . $sourceUrl;
    }
}
