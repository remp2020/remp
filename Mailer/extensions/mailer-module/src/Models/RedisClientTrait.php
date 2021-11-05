<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Predis\Client;

trait RedisClientTrait
{
    /** @var RedisClientFactory */
    protected $redisClientFactory;

    /** @var Client */
    private $redis;

    private $redisDatabase;

    private $redisUseKeysPrefix = false;

    public function setRedisDatabase($redisDatabase): void
    {
        $this->redisDatabase = $redisDatabase;
    }

    public function useRedisKeysPrefix(bool $usePrefix = true): void
    {
        $this->redisUseKeysPrefix = $usePrefix;
    }

    protected function redis(): Client
    {
        if (!$this->redisClientFactory || !($this->redisClientFactory instanceof RedisClientFactory)) {
            throw new RedisClientTraitException('In order to use `RedisClientTrait`, you need to initialize `RedisClientFactory $redisClientFactory` in your service');
        }

        if ($this->redis === null) {
            $this->redis = $this->redisClientFactory->getClient($this->redisDatabase, $this->redisUseKeysPrefix);
        }

        return $this->redis;
    }
}
