<?php

namespace App\Contracts\Crm;

use App\Contracts\SegmentContract;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class Segment implements SegmentContract
{
    const ENDPOINT_LIST = 'user-segments/list';

    const ENDPOINT_CHECK = 'user-segments/check';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(): Collection
    {
        $response = $this->client->get(self::ENDPOINT_LIST);
        $list = json_decode($response->getBody());
        $collection = collect($list->segments);
        return $collection;
    }

    public function check($segmentId, $userId): bool
    {
        $response = $this->client->get(self::ENDPOINT_CHECK, [
            'query' => [
                'resolver_type' => 'email',
                'resolver_value' => $userId,
                'code' => $segmentId,
            ],
        ]);
        $result = json_decode($response->getBody());
        return $result->check;
    }
}