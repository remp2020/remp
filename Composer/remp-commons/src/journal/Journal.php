<?php

namespace Remp\Journal;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

class Journal implements JournalContract
{
    const ENDPOINT_EVENT_CATEGORIES = 'journal/events/categories';

    const ENDPOINT_COMMERCE_CATEGORIES = 'journal/commerce/categories';

    const ENDPOINT_PAGEVIEW_CATEGORIES = 'journal/pageviews/categories';

    const ENDPOINT_GROUP_CATEGORY_ACTIONS = 'journal/%s/categories/%s/actions';

    const ENDPOINT_GROUP_FLAGS = 'journal/flags';

    const ENDPOINT_GENERIC_COUNT_ACTION = 'journal/%s/actions/%s/count';

    const ENDPOINT_GENERIC_COUNT = 'journal/%s/count';

    const ENDPOINT_GENERIC_SUM_ACTION = 'journal/%s/actions/%s/sum';

    const ENDPOINT_GENERIC_SUM = 'journal/%s/sum';

    const ENDPOINT_GENERIC_AVG = 'journal/%s/actions/%s/avg';

    const ENDPOINT_GENERIC_UNIQUE = 'journal/%s/actions/%s/unique/%s';

    const ENDPOINT_GENERIC_LIST = 'journal/%s/list';

    const ENDPOINT_CONCURRENTS_COUNT = 'journal/concurrents/count';

    private $client;

    private $redis;

    private $tokenProvider;

    public function __construct(Client $client, \Predis\Client $redis, TokenProvider $tokenProvider = null)
    {
        $this->client = $client;
        $this->redis = $redis;
        $this->tokenProvider = $tokenProvider;
    }

    public function categories(): array
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
        return [
            'pageviews' => $pageviewCategories,
            'commerce' => $commerceCategories,
            'events' => $eventCategories,
        ];
    }

    public function commerceCategories(): array
    {
        try {
            $commerceResponse = $this->client->get(self::ENDPOINT_COMMERCE_CATEGORIES);
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:ListCategories endpoint: {$e->getMessage()}");
        }
        return json_decode($commerceResponse->getBody());
    }

    public function flags(): array
    {
        try {
            $response = $this->client->get(self::ENDPOINT_GROUP_FLAGS);
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:ListCategories endpoint: {$e->getMessage()}");
        }
        $flags = json_decode($response->getBody(), true);
        return $flags;
    }

    public function actions($group, $category): array
    {
        try {
            $response = $this->client->get(sprintf(self::ENDPOINT_GROUP_CATEGORY_ACTIONS, $group, $category));
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:ListActions endpoint: {$e->getMessage()}");
        }

        $list = json_decode($response->getBody());
        return $list;
    }

    public function count(AggregateRequest $request): array
    {
        return $this->aggregateCall(
            $request,
            $request->getAction() ?
                $request->buildUrl(self::ENDPOINT_GENERIC_COUNT_ACTION) :
                $request->buildUrl(self::ENDPOINT_GENERIC_COUNT)
        );
    }

    public function sum(AggregateRequest $request): array
    {
        return $this->aggregateCall(
            $request,
            $request->getAction() ?
                $request->buildUrl(self::ENDPOINT_GENERIC_SUM_ACTION) :
                $request->buildUrl(self::ENDPOINT_GENERIC_SUM)
        );
    }

    public function avg(AggregateRequest $request): array
    {
        return $this->aggregateCall($request, $request->buildUrl(self::ENDPOINT_GENERIC_AVG));
    }

    public function unique(AggregateRequest $request): array
    {
        // Unique page views are distinguished by different browsers
        return $this->aggregateCall(
            $request,
            $request->buildUrlWithItem(self::ENDPOINT_GENERIC_UNIQUE, 'browsers')
        );
    }

    private function aggregateCall(AggregateRequest $request, string $url): array
    {
        try {
            $json = [
                'filter_by' => $request->getFilterBy(),
                'group_by' => $request->getGroupBy(),
            ];

            if ($this->tokenProvider) {
                $token = $this->tokenProvider->getToken();
                if ($token) {
                    $json['filter_by'][] = [
                        'tag' => 'token',
                        'values' => [$token],
                        'inverse' => false,
                    ];
                }
            }

            if ($request->getTimeAfter()) {
                $json['time_after'] = $this->roundSecondsDown($request->getTimeAfter())->format(DATE_RFC3339);
            }
            if ($request->getTimeBefore()) {
                $json['time_before'] = $this->roundSecondsDown($request->getTimeBefore())->format(DATE_RFC3339);
            }
            if ($request->getTimeHistogram()) {
                $json['time_histogram'] = $request->getTimeHistogram();
            }

            $cacheKey = $this->requestHash($url, $json);

            $body = $this->redis->get($cacheKey);
            if (!$body) {
                $response = $this->client->post($url, [
                    'json' => $json,
                ]);
                $body = $response->getBody();

                // cache body
                $this->redis->set($cacheKey, $body);
                $this->redis->expire($cacheKey, 60);
            }
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal endpoint {$url}: {$e->getMessage()}");
        } catch (ClientException $e) {
            \Log::error(json_encode([
                'url' => $url,
                'payload' => $json,
                'message' => $e->getResponse()->getBody()->getContents(),
            ]));
            throw $e;
        }

        $list = json_decode($body);
        return $list;
    }

    public function list(ListRequest $request): array
    {
        try {
            $json = [
                'select_fields' => $request->getSelect(),
                'conditions' => [
                    'filter_by' => $request->getFilterBy(),
                    'group_by' => $request->getGroupBy(),
                ],
            ];

            if ($request->getTimeAfter()) {
                $json['conditions']['time_after'] = $request->getTimeAfter()->format(DATE_RFC3339);
            }
            if ($request->getTimeBefore()) {
                $json['conditions']['time_before'] = $request->getTimeBefore()->format(DATE_RFC3339);
            }
            if ($request->getLoadTimespent()) {
                $json['load_timespent'] = true;
            }

            $response = $this->client->post($request->buildUrl(self::ENDPOINT_GENERIC_LIST), [
                'json' => $json,
            ]);
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:List endpoint: {$e->getMessage()}");
        } catch (ClientException $e) {
            \Log::error($e->getResponse()->getBody()->getContents());
            throw $e;
        }

        $list = json_decode($response->getBody());
        return $list;
    }

    public function concurrents(ConcurrentsRequest $request): array
    {
        $cacheKey = null;

        try {
            $json = [
                'filter_by' => $request->getFilterBy(),
                'group_by' => $request->getGroupBy(),
            ];

            if ($this->tokenProvider) {
                $token = $this->tokenProvider->getToken();
                if ($token) {
                    $json['filter_by'][] = [
                        'tag' => 'token',
                        'values' => [$token],
                        'inverse' => false,
                    ];
                }
            }

            if ($request->getTimeAfter()) {
                $json['time_after'] = $this->roundSecondsDown($request->getTimeAfter())->format(DATE_RFC3339);
            }
            if ($request->getTimeBefore()) {
                $json['time_before'] = $this->roundSecondsDown($request->getTimeBefore())->format(DATE_RFC3339);
            }

            $cacheKey = $this->requestHash(self::ENDPOINT_CONCURRENTS_COUNT, $json);

            $body = $this->redis->get($cacheKey);
            if (!$body) {
                $response = $this->client->post(self::ENDPOINT_CONCURRENTS_COUNT, [
                    'json' => $json,
                ]);
                $body = $response->getBody();

                // cache body
                $this->redis->set($cacheKey, $body);
                $this->redis->expire($cacheKey, 60);
            }
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal:concurrents endpoint: {$e->getMessage()}");
        } catch (ClientException $e) {
            \Log::error($e->getResponse()->getBody()->getContents());
            throw $e;
        }

        $list = json_decode($body);
        return $list;
    }

    /**
     * roundSecondsDown rounds second down to the closest multiple of 10.
     *
     * For example $dt containing 2019-05-28 16:43:28 will be modified to 2019-05-28 16:43:20.
     *
     * @param \DateTime $dt
     * @return \DateTime
     */
    private function roundSecondsDown(\DateTime $dt): \DateTime
    {
        $c = Carbon::instance($dt);
        $c->second($c->second - ($c->second % 10));
        return $c;
    }

    private function requestHash($path, $json)
    {
        return $path . '_' . md5(json_encode($json));
    }
}
