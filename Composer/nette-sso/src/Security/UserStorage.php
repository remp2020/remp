<?php

namespace Remp\NetteSso\Security;

use Nette\Http\Session;

class UserStorage extends \Nette\Bridges\SecurityHttp\SessionStorage
{
    private static $cached;

    private $ssoClient;

    public function __construct(Session $sessionHandler, Client $ssoClient)
    {
        parent::__construct($sessionHandler);
        $this->ssoClient = $ssoClient;
    }

    public function isAuthenticated(): bool
    {
        $parent = parent::isAuthenticated();
        if (!$parent) {
            return false;
        }
        if (isset(self::$cached)) {
            return self::$cached;
        }

        $token = $this->getIdentity()->token;
        if (!$token) {
            return false;
        }

        try {
            $this->ssoClient->introspect($token);
        } catch (SsoExpiredException $tokenExpired) {
            if (isset($token)) {
                try {
                    $tokenResponse = $this->ssoClient->refresh($token);
                    $this->getIdentity()->token = $tokenResponse['token'];
                } catch (SsoExpiredException $refreshExpired) {
                    self::$cached = false;
                    return false;
                }
            }
        }
        self::$cached = true;
        return true;
    }
}