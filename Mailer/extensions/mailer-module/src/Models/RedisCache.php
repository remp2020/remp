<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Predis\Client;

class RedisCache
{
    private $host;

    private $port;

    private $db;

    /** @var Client */
    private $client;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, int $db = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->db = $db;
    }

    public function client(): Client
    {
        if (!$this->client) {
            $this->client = new Client([
                'scheme' => 'tcp',
                'host'   => $this->host,
                'port'   => $this->port,
            ]);

            $this->client->select($this->db);
        }
        return $this->client;
    }
}
