<?php

namespace Remp\NetteSso\Security;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Nette\Http\IRequest;

class Client
{
    const ENDPOINT_INTROSPECT = 'api/auth/introspect';

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
     *
     * @param $token
     * @return array
     * @throws SsoExpiredException
     * @throws SsoException
     */
    public function introspect($token)
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
            $body = \GuzzleHttp\json_decode($contents);
            switch ($response->getStatusCode()) {
                case 400:
                case 401:
                    $e = new SsoExpiredException();
                    $e->redirect = $body->redirect;
                    throw $e;
                default:
                    throw new Nette\Security\AuthenticationException($contents);
            }
        }

        $user = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        return $user;
    }
}