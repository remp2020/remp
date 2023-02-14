<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Job;

use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;

class MailCache
{
    use RedisClientTrait;

    const REDIS_KEY = 'mail-queue-';
    const REDIS_PRIORITY_QUEUES_KEY = 'priority-mail-queues';

    private $host;

    private $port;

    private $db;

    public function __construct(protected RedisClientFactory $redisClientFactory)
    {
    }

    /**
     * @link https://redis.io/commands/ping
     * @param string|null $message
     * @return mixed
     */
    public function ping(string $message = null)
    {
        return $this->redis()->ping($message);
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

        return (bool)$this->redis()->sadd(static::REDIS_KEY . $queueId, [$job]);
    }

    public function getJob(int $queueId): ?string
    {
        return $this->redis()->spop(static::REDIS_KEY . $queueId);
    }

    public function getJobs(int $queueId, int $count = 1): array
    {
        return (array) $this->redis()->spop(static::REDIS_KEY . $queueId, $count);
    }

    public function hasJobs(int $queueId): bool
    {
        return $this->redis()->scard(static::REDIS_KEY . $queueId) > 0;
    }

    public function countJobs(int $queueId): int
    {
        return $this->redis()->scard(static::REDIS_KEY . $queueId);
    }

    public function jobExists(string $job, int $queueId): bool
    {
        return (bool)$this->redis()->sismember(static::REDIS_KEY . $queueId, $job);
    }

    // Mail queue
    public function removeQueue(int $queueId): bool
    {
        $res1 = $this->redis()->del([static::REDIS_KEY . $queueId]);
        $res2 = $this->redis()->zrem(static::REDIS_PRIORITY_QUEUES_KEY, $queueId);
        return $res1 && $res2;
    }

    public function pauseQueue(int $queueId): int
    {
        return $this->redis()->zadd(static::REDIS_PRIORITY_QUEUES_KEY, [$queueId => 0]);
    }

    public function restartQueue(int $queueId, int $priority): int
    {
        return $this->redis()->zadd(static::REDIS_PRIORITY_QUEUES_KEY, [$queueId => $priority]);
    }

    public function isQueueActive(int $queueId): bool
    {
        return $this->redis()->zscore(static::REDIS_PRIORITY_QUEUES_KEY, $queueId) > 0;
    }

    /**
     * getTopPriorityQueues returns array of queue-score pairs order by queue priority (descending).
     */
    public function getTopPriorityQueues(int $count = 1)
    {
        // TODO: change to zrange with "byscore" and "rev" options once we upgrade to Predis 2.0
        return $this->redis()->zrevrangebyscore(
            static::REDIS_PRIORITY_QUEUES_KEY,
            '+inf',
            1,
            [
                'withscores' => true,
                'limit' => [
                    'offset' => 0,
                    'count' => $count,
                ],
            ]
        );
    }

    public function isQueueTopPriority(int $queueId): bool
    {
        $topPriorityQueue = $this->getTopPriorityQueues();
        $selectedQueueScore = $this->redis()->zscore(static::REDIS_PRIORITY_QUEUES_KEY, $queueId);

        return isset($topPriorityQueue[$queueId]) || // topPriorityQueue is requested queue
            reset($topPriorityQueue) == $selectedQueueScore; // or requested queue has same priority as topPriorityQueue
    }
}
