<?php

namespace App\Contracts;

interface JournalContract
{
    public function count(
        string $category,
        string $action,
        \DateTime $timeAfter,
        \DateTime $timeBefore
    ): int;
}