<?php

namespace App\Contracts;

interface TrackerContract
{
    public function event(string $category, string $action, string $userId, array $fields): void;
}