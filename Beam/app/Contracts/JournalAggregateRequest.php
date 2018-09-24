<?php

namespace App\Contracts;

class JournalAggregateRequest
{
    protected $category;

    protected $action;

    protected $filterBy = [];

    protected $groupBy = [];

    protected $timeBefore;

    protected $timeAfter;

    protected $timeHistogram = [];

    public function __construct($category, $action)
    {
        $this->category = $category;
        $this->action = $action;
    }

    public function addFilter(string $tag, string ...$values)
    {
        foreach ($values as &$v) {
            $v = strval($v);
        }
        $this->filterBy[] = [
            "tag" => $tag,
            "values" => $values,
        ];
    }

    public function addGroup(string ...$tags)
    {
        $this->groupBy = array_merge($this->groupBy, $tags);
    }

    public function setTimeHistogram(string $interval, string $offset) : JournalAggregateRequest
    {
        $this->timeHistogram = [
            'interval' => $interval,
            'offset' => $offset
        ];
        return $this;
    }

    public function setTimeBefore(\DateTime $timeBefore)
    {
        $this->timeBefore = $timeBefore;
    }

    public function setTimeAfter(\DateTime $timeAfter)
    {
        $this->timeAfter = $timeAfter;
    }

    public function buildUrl($template): string
    {
        return sprintf($template, $this->category, $this->action);
    }

    public function buildUrlWithItem($template, $item): string
    {
        return sprintf($template, $this->category, $this->action, $item);
    }

    public function getFilterBy(): array
    {
        return $this->filterBy;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getTimeBefore(): ?\DateTime
    {
        return $this->timeBefore;
    }

    public function getTimeAfter(): ?\DateTime
    {
        return $this->timeAfter;
    }

    public function getTimeHistogram(): array
    {
        return $this->timeHistogram;
    }
}
