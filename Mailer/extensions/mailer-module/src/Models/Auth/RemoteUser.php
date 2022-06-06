<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class RemoteUser
{
    private $ssoHost;

    private $timeout;

    public function __construct(string $ssoHost, float $timeout = 10.0)
    {
        $this->ssoHost = $ssoHost;
        $this->timeout = $timeout;
    }

    public function remoteLogin(string $email, string $password): array
    {
        $client = new Client([
            'base_uri' => $this->ssoHost,
            'timeout' => $this->timeout,
        ]);

        try {
            $response = $client->request('POST', '/api/v1/users/login', [
                'form_params' => [
                    'source' => 'remp/Mailer',
                    'email' => $email,
                    'password' => $password,
                ],
            ]);

            $responseData = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (ClientException $clientException) {
            try {
                $data = Json::decode($clientException->getResponse()->getBody()->getContents());
            } catch (JsonException $e) {
                return ['status' => 'error', 'error' => $e->getMessage(), 'message' => 'Invalid response provided by CRM. Is SSO_ADDR configured correctly?'];
            }
            return ['status' => 'error', 'error' => $data->error, 'message' => $data->message];
        } catch (ConnectException $connectException) {
            return ['status' => 'error', 'error' => 'unavailable server', 'message' => 'Cannot connect to auth server'];
        } catch (JsonException $jsonException) {
            return ['status' => 'error', 'error' => 'wrong response', 'message' => $jsonException->getMessage()];
        }

        if (!isset($responseData['user']['roles'])) {
            return ['status' => 'error', 'error' => 'not admin', 'message' => 'Your are not admin user'];
        }

        if (in_array('superadmin', $responseData['user']['roles'], true)
            || in_array('remp/mailer', $responseData['user']['roles'], true)
        ) {
            $data = ['status' => 'ok', 'data' => $responseData];
        } else {
            return ['status' => 'error', 'error' => 'not admin', 'message' => 'Your are not authorized for this app'];
        }

        return $data;
    }

    public function userInfo(string $token): array
    {
        $client = new Client([
            'base_uri' => $this->ssoHost,
            'timeout' => $this->timeout,
        ]);

        try {
            $response = $client->request('GET', '/api/v1/user/info', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]);
        } catch (ClientException $clientException) {
            $data = json_decode($clientException->getResponse()->getBody()->getContents());
            return ['status' => 'error', 'error' => 'auth error', 'message' => $data->message];
        } catch (ConnectException $connectException) {
            return ['status' => 'error', 'error' => 'unavailable server', 'message' => 'Cannot connect to auth server'];
        }

        $responseData = json_decode($response->getBody()->getContents(), true);

        return $responseData['user'];
    }
}
