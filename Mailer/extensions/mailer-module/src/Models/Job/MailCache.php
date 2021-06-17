<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Job;

use Predis\Client;

class MailCache
{
    const REDIS_KEY = 'mail-queue-';
    const REDIS_PRIORITY_QUEUES_KEY = 'priority-mail-queues';

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

            $this->redis->select($this->db);
        }

        return $this->redis;
    }

    /**
     * @link https://redis.io/commands/ping
     * @param string|null $message
     * @return mixed
     */
    public function ping(string $message = null)
    {
        return $this->connect()->ping($message);
    }

    /**
     * Adds mail job to mail processing cache
     *
     * Note: all parameters in $params having name with suffix '_href_url' are treated as URLs that can be changed later by email sender.
     * The URL destination itself will be kept, however, e.g. tracking parameters could be added, URL shortener used.
     * Example: https://dennikn.sk/1589603/ could be changed to https://dennikn.sk/1589603/?rtm_source=email
     *
     * @param int $userId
     * @param string $email
     * @param string $templateCode
     * @param int $queueId
     * @param string|null $context
     * @param array $params contains array of key-value items that will replace variables in email and subject
     *
     * @return bool
     */
    public function addJob(int $userId, string $email, string $templateCode, int $queueId, ?string $context = null, array $params = []): bool
    {
        $job = json_encode([
            'userId' => $userId,
            'email' => $email,
            'templateCode' => $templateCode,
            'context' => $context,
            'params' => $params
        ]);

        if ($this->jobExists($job, $queueId)) {
            return false;
        }

        return (bool)$this->connect()->sadd(static::REDIS_KEY . $queueId, [$job]);
    }

    public function getJob(int $queueId): ?string
    {
        return $this->connect()->spop(static::REDIS_KEY . $queueId);
    }

    public function getJobs(int $queueId, int $count = 1): array
    {
        return (array) $this->connect()->spop(static::REDIS_KEY . $queueId, $count);
    }

    public function hasJobs(int $queueId): bool
    {
        return $this->connect()->scard(static::REDIS_KEY . $queueId) > 0;
    }

    public function jobExists(string $job, int $queueId): bool
    {
        return (bool)$this->connect()->sismember(static::REDIS_KEY . $queueId, $job);
    }

    // Mail queue
    public function removeQueue(int $queueId): bool
    {
        $res1 = $this->connect()->del([static::REDIS_KEY . $queueId]);
        $res2 = $this->connect()->zrem(static::REDIS_PRIORITY_QUEUES_KEY, $queueId);
        return $res1 && $res2;
    }

    public function pauseQueue(int $queueId): int
    {
        return $this->connect()->zadd(static::REDIS_PRIORITY_QUEUES_KEY, [$queueId => 0]);
    }

    public function restartQueue(int $queueId, int $priority): int
    {
        return $this->connect()->zadd(static::REDIS_PRIORITY_QUEUES_KEY, [$queueId => $priority]);
    }

    public function isQueueActive(int $queueId): bool
    {
        return $this->connect()->zscore(static::REDIS_PRIORITY_QUEUES_KEY, $queueId) > 0;
    }

    public function isQueueTopPriority(int $queueId): bool
    {
        $selectedQueueScore = $this->connect()->zscore(static::REDIS_PRIORITY_QUEUES_KEY, $queueId);

        $topPriorityQueue = $this->connect()->zrevrangebyscore(
            static::REDIS_PRIORITY_QUEUES_KEY,
            '+inf',
            1,
            [
                'withscores' => true,
                'limit' => [
                    'offset' => 0,
                    'count' => 1,
                ],
            ]
        );

        return isset($topPriorityQueue[$queueId]) || // topPriorityQueue is requested queue
            reset($topPriorityQueue) == $selectedQueueScore; // or requested queue has same priority as topPriorityQueue
    }
}
