<?php

namespace Remp\MailerModule\Beam;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;

class Client
{
    private $client;

    public function __construct($baseUrl, $token)
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

    public function unreadArticles($timespan, $articlesCount, array $criterias, array $userIds, array $ignoreAuthors = [])
    {
        try {
            $response = $this->client->post('api/articles/unread', [
                RequestOptions::JSON => [
                    'user_ids' => $userIds,
                    'timespan' => $timespan,
                    'articles_count' => $articlesCount,
                    'criterias' => $criterias,
                    'ignore_authors' => $ignoreAuthors,
                ]
            ]);

            return Json::decode($response->getBody(), Json::FORCE_ARRAY)['data'];
        } catch (ConnectException $connectException) {
            throw new Exception("could not connect to Beam: {$connectException->getMessage()}");
        } catch (ServerException $serverException) {
            throw new Exception("Beam service error: {$serverException->getMessage()}");
        }
    }
}
