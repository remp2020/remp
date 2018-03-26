<?php

namespace Remp\LaravelSso\Contracts;

interface SsoContract
{
    public function introspect($token): array;

    public function refresh($token): array;

    public function apiToken($token): bool;

    public function invalidate($token): array;
}
