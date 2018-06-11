<?php

namespace App\Contracts;

use Carbon\Carbon;
use App\Contracts\Remp\StatsRequest;

/**
 * Beam segments API fluent interface
 * for accessing campaigns statistics
 */
interface StatsContract
{
    /**
     * filter results by campaign id
     *
     * @param int $campaignId
     * @return StatsRequest
     */
    public function forCampaign($campaignId) : StatsRequest;

    /**
     * filter results by variant id
     *
     * @param int $variantId
     * @return StatsRequest
     */
    public function forVariant($variantId) : StatsRequest;

    /**
     * get results from events table
     *
     * @param string $categoryArg
     * @param string $actionArg
     * @return StatsRequest
     */
    public function events(string $categoryArg, string $actionArg): StatsRequest;

    /**
     * get results from pageviews table
     *
     * @return StatsRequest
     */
    public function pageviews(): StatsRequest;

    /**
     * get results from pageviews timespent table
     *
     * @return StatsRequest
     */
    public function timespent(): StatsRequest;

    /**
     * filter results by start date
     *
     * @param Carbon $from
     * @return StatsRequest
     */
    public function from(Carbon $from): StatsRequest;

    /**
     * filter results by end date
     *
     * @param Carbon $to
     * @return StatsRequest
     */
    public function to(Carbon $to): StatsRequest;

    /**
     * filter results by end date
     *
     * @param Carbon $to
     * @return StatsRequest
     */
    public function commerce(string $step): StatsRequest;

    /**
     * return time histogram buckets instead of normal results
     *
     * @param string $interval
     * @return StatsRequest
     */
    public function timeHistogram(string $interval): StatsRequest;

    /**
     * use count action on results
     *
     * @return StatsRequest
     */
    public function count(): StatsRequest;

    /**
     * use sum action on results
     *
     * @return StatsRequest
     */
    public function sum(): StatsRequest;

    /**
     * filter by any field
     *
     * @param string $field name of field
     * @param array $values array of values
     * @return StatsRequest
     */
    public function filterBy(string $field, array $values): StatsRequest;

    /**
     * group results by one or more fields
     *
     * @param string|array $field
     * @return StatsRequest
     */
    public function groupBy($field): StatsRequest;
}
