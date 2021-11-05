<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Beam;

use GuzzleHttp\Client;
use Remp\Journal\Journal;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;

class JournalFactory
{
    use RedisClientTrait;

    private $client;

    public function __construct(?string $baseUrl, RedisClientFactory $redisClientFactory)
    {
        if ($baseUrl) {
            $client = new Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            $this->client = new Journal($client, $redisClientFactory->getClient());
        }
    }

    public function getClient(): ?Journal
    {
        return $this->client;
    }
}
