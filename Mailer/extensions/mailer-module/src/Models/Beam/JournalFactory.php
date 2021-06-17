<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Beam;

use GuzzleHttp\Client;
use Remp\Journal\Journal;
use Remp\MailerModule\Models\RedisCache;

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

    public function getClient(): ?Journal
    {
        return $this->client;
    }
}
