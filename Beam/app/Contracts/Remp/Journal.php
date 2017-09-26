<?php

namespace App\Contracts\Remp;

use App\Contracts\JournalContract;
use App\Contracts\JournalException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;

class Journal implements JournalContract
{
    const ENDPOINT_EVENT_CATEGORIES = 'journal/events/categories';

    const ENDPOINT_COMMERCE_CATEGORIES = 'journal/commerce/categories';

    const ENDPOINT_PAGEVIEW_CATEGORIES = 'journal/pageviews/categories';

    const ENDPOINT_GROUP_CATEGORY_ACTIONS = 'journal/%s/categories/%s/actions';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function categories(): Collection
    {
        try {
            $pageviewResponse = $this->client->get(self::ENDPOINT_PAGEVIEW_CATEGORIES);
            $commerceResponse = $this->client->get(self::ENDPOINT_COMMERCE_CATEGORIES);
            $eventResponse = $this->client->get(self::ENDPOINT_EVENT_CATEGORIES);
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:ListCategories endpoint: {$e->getMessage()}");
        }

        $pageviewCategories = json_decode($pageviewResponse->getBody());
        $commerceCategories = json_decode($commerceResponse->getBody());
        $eventCategories = json_decode($eventResponse->getBody());
        return collect([
            'pageviews' => $pageviewCategories,
            'commerce' => $commerceCategories,
            'events' => $eventCategories,
        ]);
    }

    public function actions($group, $category): Collection
    {
        try {
            $response = $this->client->get(sprintf(self::ENDPOINT_GROUP_CATEGORY_ACTIONS, $group, $category));
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:ListActions endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        return collect($list);
    }
}
