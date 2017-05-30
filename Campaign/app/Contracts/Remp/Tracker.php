<?php

namespace App\Contracts\Remp;

use App\Contracts\TrackerContract;
use GuzzleHttp\Client;

class Tracker implements TrackerContract
{
    const ENDPOINT_TRACK_EVENT = 'track/event';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function event(
        string $category,
        string $action,
        string $userId,
        array $fields
    ):void {
        $this->client->post(self::ENDPOINT_TRACK_EVENT, [
            'json' => [
                "category" => $category,
                "action" => $action,
                "fields" => $fields,
                "system" => [
                    "user_id" => $userId,
                    "time" => (new \DateTime())->format(\DateTime::RFC3339),
                    "api_key" => "xApiKey",
                    "url" => "http://www.example.com",
                    "user_agent" => "xUserAgent",
                    "ip_address" => "127.0.0.1"
                ],
                "value" => 1,
            ],
        ]);
    }

}