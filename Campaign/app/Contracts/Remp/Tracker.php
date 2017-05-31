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
        string $url,
        string $ipAddress,
        string $userAgent,
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
                    "url" => $url,
                    "user_agent" => $userAgent,
                    "ip_address" => $ipAddress,
                ],
                "value" => 1,
            ],
        ]);
    }

}