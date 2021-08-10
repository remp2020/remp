<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Predis\Client;

class HealthChecker
{
    private const REDIS_KEY = 'mailer-healthcheck-';

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
    
    public function ping(string $processId, int $ttlSeconds = 300): bool
    {
        return (bool)$this->connect()->set(
            static::REDIS_KEY . $processId,
            '1',
            'EX', // EX - Set the specified expire time, in seconds.
            $ttlSeconds
        );
    }

    public function isHealthy(string $processId): bool
    {
        return $this->connect()->get(static::REDIS_KEY . $processId) !== null;
    }
}
