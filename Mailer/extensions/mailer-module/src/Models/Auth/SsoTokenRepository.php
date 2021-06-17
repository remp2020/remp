<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

use Remp\MailerModule\Models\Sso\Client;
use Tomaj\NetteApi\Misc\BearerTokenRepositoryInterface;

class SsoTokenRepository implements BearerTokenRepositoryInterface
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function validToken(string $token): bool
    {
        return $this->client->validToken($token);
    }

    public function ipRestrictions(string $token): string
    {
        return '*';
    }
}
