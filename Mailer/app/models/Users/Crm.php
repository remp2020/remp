<?php

namespace Remp\MailerModule\User;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tracy\Debugger;

class Crm implements IUser
{
    const ENDPOINT_LIST = 'api/v1/users/list';

    private $client;

    public function __construct($baseUrl, $token)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
    }

    public function list(array $userIds, $page)
    {
        try {
            $response = $this->client->post(self::ENDPOINT_LIST, [
                'form_params' => [
                    'user_ids' => Json::encode($userIds),
                    'page' => $page,
                ],
            ]);
            $result = Json::decode($response->getBody(), Json::FORCE_ARRAY);
            $response = null;
            return $result['users'];
        } catch (ConnectException $e) {
            throw new UserException("could not connect CRM user base: {$e->getMessage()}");
        } catch (ClientException $e) {
            Debugger::log("unable to get list of CRM users: " . $e->getResponse()->getBody()->getContents(), Debugger::WARNING);
            return [];
        } catch (JsonException $e) {
            Debugger::log("could not decode JSON response: {$response->getBody()->getContents()}", Debugger::WARNING);
            return [];
        }
    }
}
