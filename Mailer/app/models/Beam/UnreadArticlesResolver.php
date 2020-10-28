<?php
declare(strict_types=1);

namespace Remp\MailerModule\Beam;

use Remp\MailerModule\PageMeta\ContentInterface;

class UnreadArticlesResolver
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

    public function addToResolve(string $templateCode, int $userId, array $parameters): void
    {
        if (!array_key_exists($templateCode, $this->templates)) {
            $item = new \stdClass();
            $item->timespan = $parameters['timespan'];
            $item->articlesCount = $parameters['articles_count'];
            $item->criteria = $parameters['criteria'];
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
                    $item->criteria,
                    $userIdsChunk,
                    $item->ignoreAuthors
                );

                foreach ($results as $userId => $urls) {
                    $this->results[$templateCode][$userId] = $urls;
                }
            }
        }
    }

    private function checkValidParameters(string $templateCode, int $userId): void
    {
        // check enough parameters were resolved for template
        $requiredArticleCount = (int) $this->templates[$templateCode]->articlesCount;
        $userArticleCount = count($this->results[$templateCode][$userId]);

        if ($userArticleCount < $requiredArticleCount) {
            throw new UserUnreadArticlesResolveException("Template $templateCode requires $requiredArticleCount unread articles, user #$userId has only $userArticleCount, unable to send personalized email.");
        }
    }

    public function getMailParameters(string $templateCode, int $userId): array
    {
        $this->checkValidParameters($templateCode, $userId);

        $params = [];

        $headlineTitle = null;

        foreach ($this->results[$templateCode][$userId] as $i => $url) {
            if (!array_key_exists($url, $this->articlesMeta)) {
                $meta = $this->content->fetchUrlMeta($url);
                if (!$meta) {
                    throw new UserUnreadArticlesResolveException("Unable to fetch meta for url {$url} when resolving article parameters for userId: {$userId}, templateCode: {$templateCode}");
                }
                $this->articlesMeta[$url] = $meta;
            }

            $meta = $this->articlesMeta[$url];

            $counter = $i + 1;

            $params["article_{$counter}_title"] = $meta->getTitle();
            $params["article_{$counter}_description"] = $meta->getDescription();
            $params["article_{$counter}_image"] = $meta->getImage();
            $params["article_{$counter}_href_url"] = $url;

            if (!$headlineTitle) {
                $headlineTitle = $meta->getTitle();
            }
        }
        $params['headline_title'] = $headlineTitle;

        return $params;
    }
}
