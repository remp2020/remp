<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Crm;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Nette\Utils\Json;
use GuzzleHttp\Exception\ClientException;

class Client
{
    private $client;

    public function __construct(string $baseUrl, string $token)
    {
        $this->client = new GuzzleClient([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);
    }

    public function confirmUser(string $email): array
    {
        try {
            $response = $this->client->post('api/v1/users/confirm', [
                'form_params' => [
                    'email' => $email,
                ],
            ]);

            return Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (ConnectException $connectException) {
            throw new Exception("could not connect to CRM: {$connectException->getMessage()}");
        } catch (ClientException $clientException) {
            $body = Json::decode($clientException->getResponse()->getBody()->getContents(), Json::FORCE_ARRAY);
            if (isset($body['code']) && $body['code'] === 'user_not_found') {
                throw new UserNotFoundException("Unable to find user: {$clientException->getMessage()}");
            }

            throw new Exception("unable to confirm CRM user: {$clientException->getMessage()}");
        } catch (ServerException $serverException) {
            throw new Exception("unable to confirm CRM user: {$serverException->getMessage()}");
        }
    }
}
