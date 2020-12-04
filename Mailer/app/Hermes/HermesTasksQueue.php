<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Predis\Client;

class HermesTasksQueue
{
    const TASKS_KEY = 'hermes_tasks';
    const STATS_KEY = 'hermes_stats';

    /** @var Client */
    private $redis;

    private $host;

    private $port;

    private $db;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, int $db = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->db = $db;
    }

    private function connect(): Client
    {
        if (!$this->redis) {
            $this->redis = new Client([
                'scheme' => 'tcp',
                'host'   => $this->host,
                'port'   => $this->port,
            ]);
            if ($this->db) {
                $this->redis->select($this->db);
            }
        }

        return $this->redis;
    }

    // Tasks
    public function addTask(string $task, float $executeAt): bool
    {
        return $this->connect()->zadd(static::TASKS_KEY, [$task => $executeAt]) > 0;
    }

    public function getTask(): ?array
    {
        $task = $this->connect()->zrangebyscore(static::TASKS_KEY, 0, time(), [
            'LIMIT' => [
                'OFFSET' => 0,
                'COUNT' => 1,
            ]
        ]);

        if (!empty($task)) {
            $result = $this->connect()->zrem(static::TASKS_KEY, $task);
            if ($result == 1) {
                return $task;
            }
        }

        return null;
    }

    public function getAllTask(): array
    {
        return $this->connect()->zrange(static::TASKS_KEY, 0, -1, ['withscores' => true]);
    }

    // Stats
    public function incrementType(string $type): string
    {
        return $this->connect()->zincrby(static::STATS_KEY, 1, $type);
    }

    public function decrementType(string $type): string
    {
        return $this->connect()->zincrby(static::STATS_KEY, -1, $type);
    }

    public function getTypeCounts(): array
    {
        return $this->connect()->zrange(static::STATS_KEY, 0, -1, ['withscores' => true]);
    }
}
