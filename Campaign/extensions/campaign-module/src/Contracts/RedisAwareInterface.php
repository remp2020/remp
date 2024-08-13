<?php

namespace Remp\CampaignModule\Contracts;

interface RedisAwareInterface
{
    public function setRedisClient(\Predis\Client $redis): self;
}
