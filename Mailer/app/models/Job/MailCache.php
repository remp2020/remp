<?php

namespace Remp\MailerModule\Job;

use Predis\Client;

class MailCache
{
    const REDIS_KEY = 'mail-queue-';
    const REDIS_PAUSED_QUEUES_KEY = 'paused-mail-queues';

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

            $this->redis->select($this->db);
        }

        return $this->redis;
    }

    // Mail Jobs

    public function addJob($email, $templateCode, $queueId)
    {
        $job = json_encode([
            'email' => $email,
            'templateCode' => $templateCode,
        ]);

        if ($this->jobExists($job, $queueId)) {
            return true;
        }

        return (bool)$this->connect()->sadd(static::REDIS_KEY . $queueId, $job);
    }

    public function getJob($queueId)
    {
        return $this->connect()->spop(static::REDIS_KEY . $queueId);
    }

    public function hasJobs($queueId)
    {
        return $this->connect()->scard(static::REDIS_KEY . $queueId) > 0;
    }

    public function jobExists($job, $queueId)
    {
        return (bool)$this->connect()->sismember(static::REDIS_KEY . $queueId, $job);
    }

    // Mail queue
    public function removeQueue($queueId)
    {
        return $this->connect()->del([static::REDIS_KEY . $queueId]) && $this->restartQueue($queueId);
    }

    public function pauseQueue($queueId)
    {
        return $this->connect()->sadd(static::REDIS_PAUSED_QUEUES_KEY, $queueId);
    }

    public function restartQueue($queueId)
    {
        return $this->connect()->srem(static::REDIS_PAUSED_QUEUES_KEY, $queueId);
    }

    public function isQueueActive($queueId)
    {
        return !((bool)$this->connect()->sismember(static::REDIS_PAUSED_QUEUES_KEY, $queueId));
    }
}
