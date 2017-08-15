<?php

namespace App\Contracts\Remp;

use App\Contracts\JournalContract;
use App\Contracts\JournalException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;

class Journal implements JournalContract
{
    const ENDPOINT_CATEGORIES = 'journal/events/categories';

    const ENDPOINT_ACTIONS = 'journal/events/categories/%s/actions';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function categories(): Collection
    {
        try {
            $response = $this->client->get(self::ENDPOINT_CATEGORIES);
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:ListCategories endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        return collect($list);
    }

    public function actions($category): Collection
    {
        try {
            $response = $this->client->get(sprintf(self::ENDPOINT_ACTIONS, $category));
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:ListActions endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        return collect($list);
    }
}