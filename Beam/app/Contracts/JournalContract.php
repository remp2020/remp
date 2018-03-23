<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface JournalContract
{
    public function categories(): Collection;

    public function flags(): Collection;

    public function actions($group, $category): Collection;

    public function count(JournalAggregateRequest $request): Collection;

    public function sum(JournalAggregateRequest $request): Collection;

    public function list(JournalListRequest $request): Collection;
}
