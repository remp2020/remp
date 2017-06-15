<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SegmentContract
{
    const BLOOM_FILTER_CACHE_TAG = 'segment_bloom';

    public function list(): Collection;

    public function check($segmentId, $userId): bool;

    public function users($segmentId): Collection;
}