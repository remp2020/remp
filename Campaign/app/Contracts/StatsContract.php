<?php

namespace App\Contracts;

use Carbon\Carbon;
use App\Contracts\Remp\StatsRequest;

interface StatsContract
{
    const CACHE_TAG = 'stats';

    public function events(string $categoryArg, string $actionArg): StatsRequest;

    public function pageviews(): StatsRequest;

    public function timespent(): StatsRequest;

    public function from(Carbon $from): StatsRequest;

    public function to(Carbon $to): StatsRequest;

    public function commerce(string $step): StatsRequest;

    public function timeHistogram(string $interval): StatsRequest;

    public function count(): StatsRequest;

    public function sum(): StatsRequest;

    public function filterBy(string $field, array $values): StatsRequest;

    public function groupBy($field): StatsRequest;

    public function get();
}
