<?php

namespace Remp\MailerModule\Beam;

use Remp\Journal\Journal;
use Remp\MailerModule\RedisCache;

class JournalFactory
{
    private $client;
    
    public function __construct($baseUrl, RedisCache $redisCache)
    {
        if ($baseUrl) {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            $this->client = new Journal($client, $redisCache->client());
        }
    }

    public function getClient()
    {
        return $this->client;
    }
}
