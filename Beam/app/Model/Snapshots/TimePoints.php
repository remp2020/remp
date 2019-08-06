<?php
namespace App\Model\Snapshots;

class TimePoints
{
    public $toInclude = [];
    public $toExclude = [];

    /**
     * TimePoints constructor.
     *
     * @param array $toInclude
     * @param array $toExclude
     */
    public function __construct(array $toInclude, array $toExclude)
    {
        $this->toInclude = $toInclude;
        $this->toExclude = $toExclude;
    }
}
