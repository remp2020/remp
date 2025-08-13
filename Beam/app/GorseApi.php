<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class GorseApi
{
    private const POST_FEEDBACK_URL = '/api/feedback';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.gorse_recommendation.endpoint'),
            'headers' => [
                'X-API-Key' => config('services.gorse_recommendation.api_key'),
            ],
        ]);
    }

    public function insertFeedback(array $feedback)
    {
        try {
            $result = $this->client->post(self::POST_FEEDBACK_URL, [
                'json' => $feedback
            ]);

            return json_decode((string) $result->getBody(), false);
        } catch (ClientException $e) {
            throw new \Exception($e->getResponse()->getBody()->getContents());
        }
    }
}
