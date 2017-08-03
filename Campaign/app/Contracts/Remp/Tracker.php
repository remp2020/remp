<?php

namespace App\Contracts\Remp;

use App\Contracts\TrackerContract;
use App\Contracts\TrackerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Tracker implements TrackerContract
{
    const ENDPOINT_TRACK_EVENT = 'track/event';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function event(
        string $beamToken,
        string $category,
        string $action,
        string $url,
        string $ipAddress,
        string $userAgent,
        string $userId,
        array $fields
    ) {
        try {
            $this->client->post(self::ENDPOINT_TRACK_EVENT, [
                'json' => [
                    "category" => $category,
                    "action" => $action,
                    "fields" => $fields,
                    "system" => [
                        "time" => (new \DateTime())->format(\DateTime::RFC3339),
                        "property_token" => $beamToken,
                    ],
                    "user" => [
                        "ip_address" => $ipAddress,
                        "url" => $url,
                        "user_agent" => $userAgent,
                        "user_id" => $userId,
                    ],
                    "value" => 1,
                ],
            ]);
        } catch (ClientException $e) {
            throw new TrackerException($e->getMessage());
        }
    }
}
