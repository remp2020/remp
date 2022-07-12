<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Sso;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Nette\Http\IRequest;
use Nette\Utils\Json;

class Client
{
    const ENDPOINT_INTROSPECT = 'api/auth/introspect';

    const ENDPOINT_REFRESH = 'api/auth/refresh';

    const ENDPOINT_CHECK_TOKEN = 'api/auth/check-token';

    private $request;

    private $client;

    public function __construct($ssoAddr, IRequest $request)
    {
        $this->client = new GuzzleClient([
            'base_uri' => $ssoAddr,
        ]);
        $this->request = $request;
    }

    /**
     * introspect attempts to obtain user data based on the provided SSO $token.
     */
    public function introspect(?string $token): array
    {
        try {
            $response = $this->client->request('GET', self::ENDPOINT_INTROSPECT, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $contents = $response->getBody()->getContents();
            $body = Json::decode($contents);
            switch ($response->getStatusCode()) {
                case 400:
                case 401:
                    $e = new SsoExpiredException();
                    $e->redirect = $body->redirect;
                    throw $e;
                default:
                    throw new \Nette\Security\AuthenticationException($contents);
            }
        }

        $user = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        return $user;
    }

    /**
     * Return true if token is valid, otherwise return false
     */
    public function validToken(string $token): bool
    {
        try {
            $response = $this->client->request('GET', self::ENDPOINT_CHECK_TOKEN, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $contents = $response->getBody()->getContents();
            if ($response->getStatusCode() === 404) {
                return false;
            }

            throw new SsoException($contents);
        }

        return $response->getStatusCode() === 200;
    }

    public function refresh(string $token): array
    {
        try {
            $response = $this->client->request('POST', self::ENDPOINT_REFRESH, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $contents = $response->getBody()->getContents();
            $body = Json::decode($contents);
            switch ($response->getStatusCode()) {
                case 400:
                case 401:
                    $e = new SsoExpiredException();
                    $e->redirect = $body->redirect;
                    throw $e;
                default:
                    throw new \Nette\Security\AuthenticationException($contents);
            }
        }

        $tokenResponse = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        return $tokenResponse;
    }
}
