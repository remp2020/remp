<?php

namespace App\Contracts\Mailer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;

class Mailer implements MailerContract
{
    const ENDPOINT_GENERATOR_TEMPLATES = 'api/v1/mailers/generator-templates';

    const ENDPOINT_SEGMENTS = 'api/v1/segments/list';

    private $client;

    private $apiToken;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->apiToken = config('services.remp.sso.api_token');
    }

    public function segments(): Collection
    {
        try {
            $response = $this->client->get(self::ENDPOINT_SEGMENTS, [
                'headers' => ['Authorization' => 'Bearer ' . $this->apiToken]
            ]);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        }

        return collect(json_decode($response->getBody())->data);
    }

    public function generatorTemplates($generator = null): Collection
    {
        $params = [
            'headers' => ['Authorization' => 'Bearer ' . $this->apiToken]
        ];
        if ($generator) {
            $params['query'] = ['generator' => $generator];
        }
        try {

            $response = $this->client->get(self::ENDPOINT_GENERATOR_TEMPLATES, $params);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        }

        return collect(json_decode($response->getBody())->data);
    }
}
