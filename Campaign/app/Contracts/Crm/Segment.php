<?php

namespace App\Contracts\Crm;

use App\Contracts\SegmentContract;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class Segment implements SegmentContract
{
    const ENDPOINT_LIST = 'user-segments/list';

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
}