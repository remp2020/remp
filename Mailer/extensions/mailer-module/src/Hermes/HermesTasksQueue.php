<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;

class HermesTasksQueue
{
    use RedisClientTrait;

    const STATS_KEY = 'hermes_stats';

    public function __construct(RedisClientFactory $redisClientFactory)
    {
        $this->redisClientFactory = $redisClientFactory;
    }

    // Tasks
    public function addTask(string $key, string $task, float $executeAt): bool
    {
        return $this->redis()->zadd($key, [$task => $executeAt]) > 0;
    }

    public function getTask(string $key): ?array
    {
        $task = $this->redis()->zrangebyscore($key, 0, time(), [
            'LIMIT' => [
                'OFFSET' => 0,
                'COUNT' => 1,
            ]
        ]);

        if (!empty($task)) {
            $result = $this->redis()->zrem($key, $task);
            if ($result == 1) {
                return $task;
            }
        }

        return null;
    }

    // Stats
    public function incrementType(string $type): string
    {
        return $this->redis()->zincrby(static::STATS_KEY, 1, $type);
    }

    public function decrementType(string $type): string
    {
        return $this->redis()->zincrby(static::STATS_KEY, -1, $type);
    }

    public function getTypeCounts(): array
    {
        return $this->redis()->zrange(static::STATS_KEY, 0, -1, ['withscores' => true]);
    }
}
