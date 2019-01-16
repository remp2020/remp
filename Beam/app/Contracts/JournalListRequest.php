<?php

namespace App\Contracts;

class JournalListRequest
{
    protected $category;

    protected $select = [];

    protected $filterBy = [];

    protected $groupBy = [];

    protected $timeBefore;

    protected $timeAfter;

    protected $loadTimespent = false;

    public static function from($category): JournalListRequest
    {
        return new self($category);
    }

    public function __construct($category)
    {
        $this->category = $category;
    }

    public function addSelect(string ...$fields): JournalListRequest
    {
        $this->select = array_merge($this->select, $fields);
        return $this;
    }

    public function addFilter(string $tag, string ...$values): JournalListRequest
    {
        foreach ($values as &$v) {
            $v = strval($v);
        }
        $this->filterBy[] = [
            "tag" => $tag,
            "values" => $values,
        ];
        return $this;
    }

    public function addGroup(string ...$tags): JournalListRequest
    {
        $this->groupBy = array_merge($this->groupBy, $tags);
        return $this;
    }

    public function setLoadTimespent(): JournalListRequest
    {
        $this->loadTimespent = true;
        return $this;
    }

    public function setTimeBefore(\DateTime $timeBefore): JournalListRequest
    {
        $this->timeBefore = $timeBefore;
        return $this;
    }

    public function setTimeAfter(\DateTime $timeAfter): JournalListRequest
    {
        $this->timeAfter = $timeAfter;
        return $this;
    }

    public function setTime(\DateTime $timeAfter, \DateTime $timeBefore)
    {
        $this->timeAfter = $timeAfter;
        $this->timeBefore = $timeBefore;
        return $this;
    }

    public function buildUrl($template): string
    {
        return sprintf($template, $this->category);
    }

    public function getSelect(): array
    {
        return $this->select;
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

    public function getLoadTimespent(): bool
    {
        return $this->loadTimespent;
    }
}
