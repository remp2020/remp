<?php

namespace Remp\MailerModule\Generators\Dynamic;

use Remp\MailerModule\Beam\Client;
use Remp\MailerModule\Generators\Utils;
use Remp\MailerModule\PageMeta\GenericPageContent;
use Remp\MailerModule\PageMeta\TransportInterface;

class UnreadArticlesGenerator
{
    private $templates = [];

    private $results = [];

    private $articlesMeta = [];

    private $beamClient;

    private $transport;

    public function __construct(Client $beamClient, TransportInterface $transport)
    {
        $this->beamClient = $beamClient;
        $this->transport = $transport;
    }

    public function addToResolve($templateCode, $userId, $parameters): void
    {
        if (!array_key_exists($templateCode, $this->templates)) {
            $item = new \stdClass();
            $item->timespan = $parameters['timespan'];
            $item->articlesCount = $parameters['articles_count'];
            $item->criteria = $parameters['criteria'];
            $item->userIds = [];
            $this->templates[$templateCode] = $item;
        }

        $this->templates[$templateCode]->userIds[] = $userId;
    }

    public function resolve(): void
    {
        foreach ($this->templates as $templateCode => $item) {
            foreach (array_chunk($item->userIds, 1000) as $userIdsChunk) {

                $results = $this->beamClient->unreadArticles($item->timespan, $item->articlesCount, $item->criteria, $userIdsChunk);

                foreach ($results as $userId => $urls) {
                    $this->results[$templateCode][$userId] = $urls;
                }
            }
        }
    }

    public function getMailParameters($templateCode, $userId): array
    {
        $params = [];
        foreach ($this->results[$templateCode][$userId] as $i => $url) {
            if (!array_key_exists($url, $this->articlesMeta)) {
                $this->articlesMeta[$url] = Utils::fetchUrlMeta($url, new GenericPageContent(), $this->transport);
            }

            $meta = $this->articlesMeta[$url];

            $counter = $i + 1;

            $params["article_{$counter}_title"] = $meta->getTitle();
            $params["article_{$counter}_description"] = $meta->getDescription();
            $params["article_{$counter}_image"] = $meta->getImage();
            $params["article_{$counter}_url"] = $url;
        }

        return $params;
    }
}
