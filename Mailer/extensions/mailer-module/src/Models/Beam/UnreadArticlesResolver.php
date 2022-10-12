<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Beam;

use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;

class UnreadArticlesResolver
{
    private $templates = [];

    private $requiredTemplateArticlesCount = [];

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

    public function addToResolveQueue(string $templateCode, int $userId, array $parameters): void
    {
        if (!array_key_exists($templateCode, $this->templates)) {
            $item = new \stdClass();
            $item->timespan = $parameters['timespan'];
            $item->articlesCount = $parameters['articles_count'];
            $item->criteria = $parameters['criteria'];
            $item->ignoreAuthors = $parameters['ignore_authors'] ?? [];
            $item->ignoreContentTypes = $parameters['ignore_content_types'] ?? [];
            $item->userIds = [];
            $this->templates[$templateCode] = $item;
        }
        // $this->templates queue is cleaned after each resolve, therefore store number of required articles
        // in an additional property - so we can check later that enough parameters are resolved for each template
        if (!array_key_exists($templateCode, $this->requiredTemplateArticlesCount)) {
            $this->requiredTemplateArticlesCount[$templateCode] = $parameters['articles_count'];
        }
        $this->templates[$templateCode]->userIds[] = $userId;
    }

    /**
     * Resolves all parameters added to resolve queue (and subsequently empties the queue)
     * Resolved parameters can be retrieved using getResolvedMailParameters() method
     * @throws \Exception
     */
    public function resolve(): void
    {
        foreach ($this->templates as $templateCode => $item) {
            foreach (array_chunk($item->userIds, 1000) as $userIdsChunk) {
                $results = $this->beamClient->unreadArticles(
                    $item->timespan,
                    $item->articlesCount,
                    $item->criteria,
                    $userIdsChunk,
                    $item->ignoreAuthors,
                    $item->ignoreContentTypes
                );

                foreach ($results as $userId => $urls) {
                    $this->results[$templateCode][$userId] = $urls;
                }
            }
        }
        $this->templates = [];
    }

    public function getResolvedMailParameters(string $templateCode, int $userId): array
    {
        $this->checkValidParameters($templateCode, $userId);

        $params = [];

        $headlineTitle = null;

        foreach ($this->results[$templateCode][$userId] as $i => $url) {
            if (!array_key_exists($url, $this->articlesMeta)) {
                try {
                    $meta = $this->content->fetchUrlMeta($url);
                } catch (InvalidUrlException $e) {
                    $meta = null;
                }
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

    private function checkValidParameters(string $templateCode, int $userId): void
    {
        // check enough parameters were resolved for template
        $requiredArticleCount = (int) $this->requiredTemplateArticlesCount[$templateCode];
        $userArticleCount = count($this->results[$templateCode][$userId]);

        if ($userArticleCount < $requiredArticleCount) {
            throw new UserUnreadArticlesResolveException("Template $templateCode requires $requiredArticleCount unread articles, user #$userId has only $userArticleCount, unable to send personalized email.");
        }
    }
}
