<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CrmApi
{
    private const POST_USERS_LIST_URL = '/api/v1/users/list';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.crm.addr'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.crm.api_key'),
            ],
        ]);
    }

    public function getUsersList(array $userIds, int $page = 1)
    {
        try {
            $result = $this->client->post(self::POST_USERS_LIST_URL, [
                'form_params' => [
                    'user_ids' => json_encode(array_values($userIds)),
                    'with_uuid' => true,
                    'page' => $page
                ]
            ]);

            return json_decode((string) $result->getBody(), false);
        } catch (ClientException $e) {
            throw new \Exception($e->getResponse()->getBody()->getContents());
        }
    }
}
