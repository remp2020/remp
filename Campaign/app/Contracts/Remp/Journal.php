<?php

namespace App\Contracts\Remp;

use App\Contracts\JournalContract;
use GuzzleHttp\Client;

class Journal implements JournalContract
{
    const ENDPOINT_TRACK_EVENT = 'track/event';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function count(
        string $category,
        string $action,
        \DateTime $timeAfter,
        \DateTime $timeBefore
    ):int {
        return 0;
    }
}