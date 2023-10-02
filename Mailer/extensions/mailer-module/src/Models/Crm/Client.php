<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Crm;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
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

    public function validateEmail(string $email): array
    {
        try {
            $response = $this->client->post('api/v1/users/set-email-validated', [
                RequestOptions::FORM_PARAMS => [
                    'email' => $email,
                ],
            ]);

            return Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (ConnectException $connectException) {
            throw new Exception("could not connect to CRM: {$connectException->getMessage()}");
        } catch (ClientException $clientException) {
            $body = Json::decode($clientException->getResponse()->getBody()->getContents(), Json::FORCE_ARRAY);
            if (isset($body['code']) && $body['code'] === 'email_not_found') {
                throw new UserNotFoundException("Unable to find email: {$clientException->getMessage()}");
            }
            throw new Exception("unable to confirm CRM user: {$clientException->getMessage()}");
        } catch (ServerException $serverException) {
            throw new Exception("unable to confirm CRM user: {$serverException->getMessage()}");
        }
    }

    /**
     * @param string[] $emails
     **/
    public function validateMultipleEmails(array $emails): mixed
    {
        // An empty post request would just waste cpu cycles
        if (count($emails) === 0) {
            return [];
        }
        
        try {
            $response = $this->client->post('api/v2/users/set-email-validated', [
                RequestOptions::JSON => ['emails' => $emails]
            ]);

            return Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (ConnectException $connectException) {
            throw new Exception("could not connect to CRM: {$connectException->getMessage()}");
        } catch (ClientException $clientException) {
            throw new Exception("unable to confirm CRM user: {$clientException->getMessage()}");
        } catch (ServerException $serverException) {
            throw new Exception("unable to confirm CRM user: {$serverException->getMessage()}");
        }
    }

    public function userTouch(int $userId): bool
    {
        try {
            $response = $this->client->get('api/v1/users/touch', [
                RequestOptions::QUERY => [
                    'id' => $userId,
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (ClientException|ServerException $exception) {
            throw new Exception("Unable to touch user: {$exception->getMessage()}");
        } catch (ConnectException $exception) {
            throw new Exception("Could not connect to CRM: {$exception->getMessage()}");
        }
    }
}
