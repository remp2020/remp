<?php

namespace Remp\LaravelSso\Contracts;

interface SsoContract
{
    public function introspect($token): array;

    public function refresh($token): array;
}