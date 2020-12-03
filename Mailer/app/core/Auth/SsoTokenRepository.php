<?php
declare(strict_types=1);

namespace Remp\MailerModule\Auth;

use Remp\NetteSso\Security\Client;
use Tomaj\NetteApi\Misc\BearerTokenRepositoryInterface;

class SsoTokenRepository implements BearerTokenRepositoryInterface
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function validToken($token)
    {
        return $this->client->validToken($token);
    }

    public function ipRestrictions($token)
    {
        return '*';
    }
}
