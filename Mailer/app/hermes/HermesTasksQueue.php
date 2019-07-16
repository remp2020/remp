<?php

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

    public function __construct($host = '127.0.0.1', $port = 6379, $db = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->db = $db;
    }

    private function connect()
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
    public function addTask(string $task, float $executeAt)
    {
        return $this->connect()->zadd(static::TASKS_KEY, [$task => $executeAt]) > 0;
    }

    public function getTask()
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

        return false;
    }

    public function getAllTask()
    {
        return $this->connect()->zrange(static::TASKS_KEY, 0, -1, ['withscores' => true]);
    }

    // Stats
    public function incrementType($type)
    {
        return $this->connect()->zincrby(static::STATS_KEY, 1, $type);
    }

    public function decrementType($type)
    {
        return $this->connect()->zincrby(static::STATS_KEY, -1, $type);
    }

    public function getTypeCounts()
    {
        return $this->connect()->zrange(static::STATS_KEY, 0, -1, ['withscores' => true]);
    }
}
