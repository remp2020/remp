<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SegmentContract
{
    public function list(): Collection;
}