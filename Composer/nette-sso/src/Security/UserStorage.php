<?php

namespace Remp\NetteSso\Security;

use Nette\Http\Session;

class UserStorage extends \Nette\Http\UserStorage
{
    private static $cached;

    private $ssoClient;

    public function __construct(Session $sessionHandler, Client $ssoClient)
    {
        parent::__construct($sessionHandler);
        $this->ssoClient = $ssoClient;
    }

    public function isAuthenticated()
    {
        $parent = parent::isAuthenticated();
        if (!$parent) {
            return false;
        }
        if (isset(self::$cached)) {
            return self::$cached;
        }

        $token = $this->getIdentity()->token;
        try {
            $this->ssoClient->introspect($token);
        } catch (SsoExpiredException $e) {
            self::$cached = false;
            return false;
        }
        self::$cached = true;
        return true;
    }
}