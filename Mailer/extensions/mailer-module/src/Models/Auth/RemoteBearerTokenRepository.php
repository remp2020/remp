<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Nette\Security\AuthenticationException;
use Tomaj\NetteApi\Misc\TokenRepositoryInterface;

class RemoteBearerTokenRepository implements TokenRepositoryInterface
{
    const ENDPOINT_CHECK_TOKEN = 'api/v1/token/check';

    private $client;

    public function __construct(string $baseUrl)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl
        ]);
    }

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
            if ($response->getStatusCode() === 403) {
                return false;
            }

            throw new AuthenticationException($contents);
        }

        return $response->getStatusCode() === 200;
    }

    public function ipRestrictions(string $token): string
    {
        return '*';
    }
}
