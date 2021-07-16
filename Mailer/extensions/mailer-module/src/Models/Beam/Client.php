<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Beam;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;

class Client
{
    private $client;

    public function __construct(?string $baseUrl, ?string $token)
    {
        if ($baseUrl) {
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ]
            ]);
        }
    }

    public function unreadArticles(
        $timespan,
        $articlesCount,
        array $criteria,
        array $userIds,
        array $ignoreAuthors = [],
        array $ignoreContentTypes = []
    ): array {
        if (!$this->client) {
            throw new \Exception('Beam Client is not configured');
        }

        try {
            $response = $this->client->post('api/articles/unread', [
                RequestOptions::JSON => [
                    'user_ids' => $userIds,
                    'timespan' => $timespan,
                    'articles_count' => $articlesCount,
                    'criteria' => $criteria,
                    'ignore_authors' => $ignoreAuthors,
                    'ignore_content_types' => $ignoreContentTypes,
                ]
            ]);

            return Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY)['data'];
        } catch (ConnectException $connectException) {
            throw new Exception("could not connect to Beam: {$connectException->getMessage()}");
        } catch (ServerException $serverException) {
            throw new Exception("Beam service error: {$serverException->getMessage()}");
        }
    }
}
