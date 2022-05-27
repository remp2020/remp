<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

class HealthChecker
{
    use RedisClientTrait;

    private const REDIS_KEY = 'mailer-healthcheck-';

    public function __construct(RedisClientFactory $redisClientFactory)
    {
        $this->redisClientFactory = $redisClientFactory;
    }
    
    public function ping(string $processId, int $ttlSeconds = 300): bool
    {
        return (bool) $this->redis()->set(
            self::REDIS_KEY . $processId,
            '1',
            'EX', // EX - Set the specified expire time, in seconds.
            $ttlSeconds
        );
    }

    public function isHealthy(string $processId): bool
    {
        return $this->redis()->get(self::REDIS_KEY . $processId) !== null;
    }
}
