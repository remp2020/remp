<?php

namespace Remp\CampaignModule\Contracts\Remp;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Remp\CampaignModule\Contracts\StatsContract;
use Remp\CampaignModule\Contracts\StatsException;
use GuzzleHttp\Exception\ClientException;

class StatsRequest implements StatsContract
{
    /** @var Client guzzle http client */
    private $client;

    /** @var string timezone offset */
    private $timeOffset;

    /** @var string action */
    private $action;

    /** @var string table */
    private $table;

    /** @var array url arguments */
    private $args = [];

    /** @var string from date object */
    private $from;

    /** @var string to date object */
    private $to;

    /** @var array group by fields */
    private $groupBy = [];

    /** @var array filter by fields */
    private $filterBy = [];

    /** @var array time histogram options */
    private $timeHistogram = [];

    public function __construct(Client $client, $timeOffset = null)
    {
        $this->timeOffset = is_null($timeOffset) ? "0h" : $timeOffset;
        $this->client = $client;
    }

    public function forVariant($variantId) : StatsRequest
    {
        $this->filterBy("rtm_variant", $variantId);
        return $this;
    }

    public function forVariants(array $variantIds): StatsRequest
    {
        foreach ($variantIds as $variantId) {
            $this->forVariant($variantId);
        }
        return $this;
    }

    public function events(string $categoryArg, string $actionArg) : StatsRequest
    {
        $this->args['categories'] = $categoryArg;
        $this->args['actions'] = $actionArg;
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

    public function from(Carbon $from): StatsRequest
    {
        $this->from = $from->setTimezone('UTC')->toRfc3339String();
        return $this;
    }

    public function to(Carbon $to): StatsRequest
    {
        $this->to = $to->setTimezone('UTC')->toRfc3339String();
        return $this;
    }

    public function commerce(string $step) : StatsRequest
    {
        $this->args['steps'] = $step;
        $this->table = "commerce";
        return $this;
    }

    public function timeHistogram(string $interval, ?string $timeZone = null) : StatsRequest
    {
        $this->timeHistogram = [
            'interval' => $interval,
            'offset' => $this->timeOffset
        ];
        if ($timeZone) {
            $this->timeHistogram['time_zone'] = $timeZone;
        }

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

    public function filterBy(string $field, ...$values) : StatsRequest
    {
        if (isset($this->filterBy[$field])) {
            $this->filterBy[$field]['values'] = array_merge(
                $this->filterBy[$field]['values'],
                $values
            );
            return $this;
        }

        $this->filterBy[$field] = [
            'tag' => $field,
            'values' => $values,
        ];
        return $this;
    }

    public function groupBy(...$fields) : StatsRequest
    {
        $this->groupBy = array_merge($this->groupBy, $fields);

        return $this;
    }

    private function url() : string
    {
        $url = 'journal/' . $this->table;

        foreach ($this->args as $arg => $val) {
            $url .= '/' . $arg . '/' . $val;
        }

        if ($this->action) {
            $url .= '/' . $this->action;
        }

        return $url;
    }

    public function get()
    {
        $payload = [
            'filter_by' => array_values($this->filterBy),
            'group_by' => $this->groupBy,
        ];

        if ($this->from) {
            $payload['time_after'] = $this->from;
        }

        if ($this->to) {
            $payload['time_before'] = $this->to;
        }

        if ($this->timeHistogram) {
            $payload['time_histogram'] = $this->timeHistogram;
        }

        try {
            $result = $this->client->post($this->url(), [
                RequestOptions::JSON => $payload,
                RequestOptions::HEADERS => [
                    'Accept' => 'application/vnd.goa.error, application/vnd.count+json; type=collection',
                    'Content-Type' => 'application/json'
                ]
            ]);
        } catch (ClientException $e) {
            throw new StatsException('bad request', 400, $e);
        }

        $stream = $result->getBody();

        try {
            $data = \GuzzleHttp\Utils::jsonDecode($stream->getContents());
        } catch (\Exception $e) {
            throw new StatsException('cannot decode json response', 400, $e);
        }

        return $data;
    }
}
