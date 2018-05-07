<?php

namespace Remp\MailerModule\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Nette\Security\AuthenticationException;
use Tomaj\NetteApi\Misc\BearerTokenRepositoryInterface;

class RemoteBearerTokenRepository implements BearerTokenRepositoryInterface
{
    private $client;

    private $endpointCheckToken;

    public function __construct($baseUrl, $endpointCheckToken)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl
        ]);
        $this->endpointCheckToken = $endpointCheckToken;
    }

    public function validToken($token)
    {
        try {
            $response = $this->client->request('GET', $this->endpointCheckToken, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $contents = $response->getBody()->getContents();
            if ($response->getStatusCode() === 403) {
                return false;
            }

            throw new AuthenticationException($contents);
        }

        return $response->getStatusCode() === 200;
    }

    public function ipRestrictions($token)
    {
        return '*';
    }
}
