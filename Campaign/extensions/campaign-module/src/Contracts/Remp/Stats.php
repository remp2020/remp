<?php

namespace Remp\CampaignModule\Contracts\Remp;

use GuzzleHttp\Client;
use Remp\CampaignModule\Contracts\StatsContract;
use Carbon\Carbon;

class Stats implements StatsContract
{
    private $client;
    private $timeOffset;

    public function __construct(Client $client, $timeOffset = null)
    {
        $this->client = $client;
        $this->timeOffset = $timeOffset;
    }

    public function forVariant($variantId) : StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->forVariant($variantId);
    }

    public function forVariants(array $variantIds) : StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->forVariants($variantIds);
    }

    public function events(string $categoryArg, string $actionArg): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->events($categoryArg, $actionArg);
    }

    public function pageviews(): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->pageviews();
    }

    public function timespent(): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->timespent();
    }

    public function from(Carbon $from): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->from($from);
    }

    public function to(Carbon $to): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->to($to);
    }

    public function commerce(string $step): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->commerce($step);
    }

    public function timeHistogram(string $interval): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->timeHistogram($interval);
    }

    public function count(): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->count();
    }

    public function sum(): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->sum();
    }

    public function filterBy(string $field, ...$values): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->filterBy($field, $values);
    }

    public function groupBy(...$fields): StatsRequest
    {
        return (new StatsRequest($this->client, $this->timeOffset))->groupBy($fields);
    }
}
