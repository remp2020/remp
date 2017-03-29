<?php

namespace Remp\MailerModule\Auth;

use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;

class Authenticator implements IAuthenticator
{
    /** @var RemoteUser */
    private $remoteUser;

    public function __construct(RemoteUser $remoteUser)
    {
        $this->remoteUser = $remoteUser;
    }

    public function authenticate(array $credentials)
    {
        list($email, $password) = $credentials;

        $result = $this->remoteUser->remoteLogin($email, $password);
        if ($result['status'] == 'error') {
            throw new AuthenticationException($result['message']);
        }

        $user = $result['data']['user'];

        return new Identity($user['id'], 'admin', ['email' => $user['email'], 'token' => $result['data']['access']['token'], 'first_name' => $user['first_name'], 'last_name' => $user['last_name']]);
    }
}
