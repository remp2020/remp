<?php

namespace Remp\MailerModule\Generators\Dynamic;

use Remp\MailerModule\Beam\Client;
use Remp\MailerModule\PageMeta\ContentInterface;

class UnreadArticlesGenerator
{
    private $templates = [];

    private $results = [];

    private $articlesMeta = [];

    private $beamClient;

    private $content;

    public function __construct(
        Client $beamClient,
        ContentInterface $content
    ) {
    
        $this->beamClient = $beamClient;
        $this->content = $content;
    }

    public function addToResolve($templateCode, $userId, $parameters): void
    {
        if (!array_key_exists($templateCode, $this->templates)) {
            $item = new \stdClass();
            $item->timespan = $parameters['timespan'];
            $item->articlesCount = $parameters['articles_count'];
            $item->criterias = $parameters['criterias'];
            $item->ignoreAuthors = $parameters['ignore_authors'] ?? [];
            $item->userIds = [];
            $this->templates[$templateCode] = $item;
        }

        $this->templates[$templateCode]->userIds[] = $userId;
    }

    public function resolve(): void
    {
        foreach ($this->templates as $templateCode => $item) {
            foreach (array_chunk($item->userIds, 1000) as $userIdsChunk) {
                $results = $this->beamClient->unreadArticles(
                    $item->timespan,
                    $item->articlesCount,
                    $item->criterias,
                    $userIdsChunk,
                    $item->ignoreAuthors
                );

                foreach ($results as $userId => $urls) {
                    $this->results[$templateCode][$userId] = $urls;
                }
            }
        }
    }

    public function getMailParameters($templateCode, $userId): array
    {
        $params = [];

        $headlineTitle = null;

        foreach ($this->results[$templateCode][$userId] as $i => $url) {
            if (!array_key_exists($url, $this->articlesMeta)) {
                $this->articlesMeta[$url] = $this->content->fetchUrlMeta($url);
            }

            $meta = $this->articlesMeta[$url];

            $counter = $i + 1;

            $params["article_{$counter}_title"] = $meta->getTitle();
            $params["article_{$counter}_description"] = $meta->getDescription();
            $params["article_{$counter}_image"] = $meta->getImage();
            $params["article_{$counter}_url"] = $url;

            if (!$headlineTitle) {
                $headlineTitle = $meta->getTitle();
            }
        }
        $params['headline_title'] = $headlineTitle;

        return $params;
    }
}
