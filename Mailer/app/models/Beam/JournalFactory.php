<?php

namespace Remp\MailerModule\Beam;

use Remp\Journal\Journal;

class JournalFactory
{
    private $client;

    public function __construct($baseUrl)
    {
        if ($baseUrl) {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            $this->client = new Journal($client);
        }
    }

    public function getClient()
    {
        return $this->client;
    }
}