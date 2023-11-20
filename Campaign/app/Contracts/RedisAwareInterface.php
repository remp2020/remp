<?php

namespace App\Contracts;

interface RedisAwareInterface
{
    public function setRedisClient(\Predis\Client $redis): self;
}
