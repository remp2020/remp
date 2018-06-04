<?php

namespace App\Contracts\Remp;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use App\Contracts\StatsContract;
use GuzzleHttp\Exception\ClientException;

class StatsRequest implements StatsContract
{
    private $client;
    private $timeOffset;

    private $action;
    private $table;
    private $actionArg;
    private $categoryArg;

    private $from;
    private $to;
    private $groupBy = [];
    private $filterBy = [];
    private $timeHistogram = [];

    public function __construct(Client $client, $timeOffset)
    {
        $this->timeOffset = $timeOffset;
        $this->client = $client;
        return $this;
    }

    public function events(string $categoryArg, string $actionArg) : StatsRequest
    {
        $this->categoryArg = $categoryArg;
        $this->actionArg = $actionArg;
        $this->table = "events";
        return $this;
    }

    public function pageviews() : StatsRequest
    {
        $this->table = "pageviews";
        return $this;
    }

    public function timespent() : StatsRequest
    {
        $this->table = "pageviews";
        $this->action = "sum";
        return $this;
    }

    public function from(\DateTime $from): StatsRequest
    {
        $this->from = $from;
    }

    public function to(\DateTime $to): StatsRequest
    {
        $this->to = $to;
    }

    public function commerce() : StatsRequest
    {
        $this->table = "commerce";
        return $this;
    }

    public function timeHistogram(string $interval) : StatsRequest
    {
        $this->timeHistogram = compact($interval, $this->timeOffset);
        return $this;
    }

    public function count() : StatsRequest
    {
        $this->action = 'count';
        return $this;
    }

    public function sum() : StatsRequest
    {
        $this->action = 'sum';
        return $this;
    }

    public function filterBy(string $field, array $values) : StatsRequest
    {
        $this->filterBy[] = [
            'tag' => $field,
            'values' => $values
        ];
    }

    public function groupBy($field) : StatsRequest
    {
        if (is_string($field)) {
            $this->groupBy[] = $field;
        } else {
            $this->groupBy = array_merge($this->groupBy, $field);
        }

        return $this;
    }

    private function url() : string
    {
        $url = 'journal' . DIRECTORY_SEPARATOR . $this->table;

        if ($this->categoryArg) {
            $url .= DIRECTORY_SEPARATOR . 'categories' . DIRECTORY_SEPARATOR . $this->categoryArg;
        }

        if ($this->actionArg) {
            $url .= DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . $this->actionArg;
        }

        if ($this->action) {
            $url .= DIRECTORY_SEPARATOR . $this->action;
        }

        return $url;
    }

    public function get()
    {
        $payload = [
            'filter_by' => $this->filterBy,
            'group_by' => $this->groupBy,
        ];

        if ($this->from) {
            $payload['form'] = $this->from;
        }

        if ($this->to) {
            $payload['to'] = $this->to;
        }

        if ($this->timeHistogram) {
            $payload['time_histogram'] = $this->timeHistogram;
        }

        $result = $this->client->post($this->url(), [
            RequestOptions::JSON => $payload,
            RequestOptions::HEADERS => [
                'Accept' => 'application/vnd.goa.error, application/vnd.count+json; type=collection',
                'Content-Type' => 'application/json'
            ]
        ]);

        $stream = $result->getBody();

        return json_decode($stream->getContents());
    }

}
