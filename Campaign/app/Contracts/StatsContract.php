<?php

namespace App\Contracts;

use App\Contracts\Remp\StatsRequest;

interface StatsContract
{
    const CACHE_TAG = 'stats';

    public function events(string $categoryArg, string $actionArg): StatsRequest;

    public function pageviews(): StatsRequest;

    public function timespent(): StatsRequest;

    public function from(\DateTime $from): StatsRequest;

    public function to(\DateTime $to): StatsRequest;

    public function commerce(): StatsRequest;

    public function timeHistogram(string $interval): StatsRequest;

    public function count(): StatsRequest;

    public function sum(): StatsRequest;

    public function filterBy(string $field, array $values): StatsRequest;

    public function groupBy($field): StatsRequest;

    public function get();
}
