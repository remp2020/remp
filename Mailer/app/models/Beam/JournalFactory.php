<?php
declare(strict_types=1);

namespace Remp\MailerModule\Beam;

use Remp\Journal\Journal;
use Remp\MailerModule\RedisCache;
use GuzzleHttp\Client;

class JournalFactory
{
    private $client;
    
    public function __construct(?string $baseUrl, ?RedisCache $redisCache)
    {
        if ($baseUrl) {
            $client = new Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            $this->client = new Journal($client, $redisCache->client());
        }
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }
}
