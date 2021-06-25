<?php

namespace Remp\Journal;

class ListRequest
{
    protected $category;

    protected $select = [];

    protected $filterBy = [];

    protected $groupBy = [];

    protected $timeBefore;

    protected $timeAfter;

    protected $loadTimespent = false;

    public static function from($category): ListRequest
    {
        return new self($category);
    }

    public function __construct($category)
    {
        $this->category = $category;
    }

    public function addSelect(string ...$fields): ListRequest
    {
        $this->select = array_merge($this->select, $fields);
        return $this;
    }

    public function addInverseFilter(string $tag, ...$values): ListRequest
    {
        foreach ($values as &$v) {
            $v = (string) $v;
        }
        $this->filterBy[] = [
            "tag" => $tag,
            "values" => $values,
            "inverse" => true,
        ];
        return $this;
    }

    public function addFilter(string $tag, ...$values): ListRequest
    {
        foreach ($values as &$v) {
            $v = (string) $v;
        }
        $this->filterBy[] = [
            "tag" => $tag,
            "values" => $values,
            "inverse" => false,
        ];
        return $this;
    }

    public function addGroup(string ...$tags): ListRequest
    {
        $this->groupBy = array_merge($this->groupBy, $tags);
        return $this;
    }

    public function setLoadTimespent(): ListRequest
    {
        $this->loadTimespent = true;
        return $this;
    }

    public function setTimeBefore(\DateTime $timeBefore): ListRequest
    {
        $this->timeBefore = $timeBefore;
        return $this;
    }

    public function setTimeAfter(\DateTime $timeAfter): ListRequest
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
