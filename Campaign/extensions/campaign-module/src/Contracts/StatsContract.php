<?php

namespace Remp\CampaignModule\Contracts;

use Carbon\Carbon;
use Remp\CampaignModule\Contracts\Remp\StatsRequest;

/**
 * Beam segments API fluent interface
 * for accessing campaigns statistics
 */
interface StatsContract
{
    /**
     * filter results by variant id
     *
     * @param int $variantId
     * @return StatsRequest
     */
    public function forVariant($variantId) : StatsRequest;

    /**
     * filter results by variant ids
     *
     * @param array $variantIds
     * @return StatsRequest
     */
    public function forVariants(array $variantIds) : StatsRequest;

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
     * get results from commerce table
     *
     * @param string $step
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
    public function filterBy(string $field, ...$values): StatsRequest;

    /**
     * group results by one or more fields
     *
     * @param array $fields
     * @return StatsRequest
     */
    public function groupBy(...$fields): StatsRequest;
}
