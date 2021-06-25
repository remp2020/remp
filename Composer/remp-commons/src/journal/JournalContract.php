<?php

namespace Remp\Journal;

interface JournalContract
{
    public function categories(): array;

    public function commerceCategories(): array;

    public function flags(): array;

    public function actions($group, $category): array;

    public function count(AggregateRequest $request): array;

    public function sum(AggregateRequest $request): array;

    public function avg(AggregateRequest $request): array;

    public function unique(AggregateRequest $request): array;

    public function list(ListRequest $request): array;

    public function concurrents(ConcurrentsRequest $request): array;
}
