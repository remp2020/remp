<?php

namespace Remp\Mailer\Models;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

class WebClient
{
    private $client;

    public function __construct(string $baseUrl)
    {
        $this->client = new GuzzleClient([
            'base_uri' => $baseUrl,
        ]);
    }

    public function getEconomyPostsLast24Hours()
    {
        $now = new DateTime();
        $yesterday = (clone $now)->modify("-1 day");

        try {
            $response = $this->client->get('api/v2/reader/posts', [
                'query' => [
                    'category' => 'ekonomika',
                    'datetime_after' => $yesterday,
                    'datetime_before' => $now,
                ],
            ]);

            return Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (ClientException $clientException) {
            throw new Exception("unable to get economy posts: {$clientException->getMessage()}");
        }
    }
}
