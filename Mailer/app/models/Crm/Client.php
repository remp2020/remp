<?php

namespace Remp\MailerModule\Crm;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Nette\Utils\Json;

class Client
{
    private $client;

    public function __construct($baseUrl, $token)
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);
    }

    public function confirmUser(string $email)
    {
        try {
            $response = $this->client->post('api/v1/users/confirm', [
                'form_params' => [
                    'email' => $email,
                ],
            ]);

            return Json::decode($response->getBody(), Json::FORCE_ARRAY);
        } catch (ConnectException $connectException) {
            throw new Exception("could not connect to CRM: {$connectException->getMessage()}");
        } catch (ServerException $serverException) {
            throw new Exception("unable to confirm CRM user: {$serverException->getMessage()}");
        }
    }
}