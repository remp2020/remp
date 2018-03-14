<?php

namespace Remp\LaravelSso\Contracts\Jwt;

use Remp\LaravelSso\Contracts\JwtException;
use Remp\LaravelSso\Contracts\SsoException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard as AuthGuard;
use Illuminate\Contracts\Session\Session;

class Guard implements AuthGuard
{
    const JWT_USER_SESSION = 'jwt.user';

    const JWT_TOKEN_SESSION = 'jwt.token';

    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function authenticate()
    {
        throw new JwtException("authenticating?");
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return boolval($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return $this->session->get(self::JWT_USER_SESSION) === null;
    }

    /**
     * Get the currently authenticated user.
     * @return Authenticatable|null
     * @throws SsoException
     */
    public function user()
    {
        $sessionUser = $this->session->get(self::JWT_USER_SESSION);
        if (!$sessionUser) {
            return null;
        }
        $user = unserialize($sessionUser);
        if (! $user instanceof Authenticatable) {
            throw new SsoException("stored JWT user is not instance of Authenticatable");
        }
        return $user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        return $this->user()->getAuthIdentifier();
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     * @throws JwtException
     */
    public function validate(array $credentials = [])
    {
        throw new JwtException("jwt guard doesn't support credentials validation");
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $sessionUser = serialize($user);
        $this->session->put(self::JWT_USER_SESSION, $sessionUser);
    }

    /**
     * setToken stores JWT token to session.
     *
     * @param $token
     */
    public function setToken($token)
    {
        $this->session->put(self::JWT_TOKEN_SESSION, $token);
    }

    /**
     * getToken retrieves stored token from session.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->session->get(self::JWT_TOKEN_SESSION);
    }

    public function getName()
    {
        return self::JWT_USER_SESSION;
    }

    public function logout()
    {
        $this->session->remove(self::JWT_USER_SESSION);
    }
}
