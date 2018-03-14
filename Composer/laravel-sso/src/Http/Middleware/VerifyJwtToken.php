<?php

namespace Remp\LaravelSso\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use League\Uri\Components\Query;
use League\Uri\Schemes\Http;
use Remp\LaravelSso\Contracts\Jwt\Guard;
use Remp\LaravelSso\Contracts\Jwt\User;
use Remp\LaravelSso\Contracts\SsoContract;
use Remp\LaravelSso\Contracts\SsoExpiredException;

class VerifyJwtToken
{
    private $sso;

    private $guard;

    public function __construct(SsoContract $sso, Guard $guard)
    {
        $this->sso = $sso;
        $this->guard = $guard;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $this->guard->getToken();
        try {
            if ($request->has('token')) {
                return $this->handleCallback($request);
            }

            // invalidate token after logging user out
            if ($token && $this->guard->guest()) {
                $tokenInvalidated = $this->sso->invalidate($token);
                $this->guard->setToken(null);
                return redirect($tokenInvalidated['redirect']);
            }

            // check whether guard has a user
            if (!$this->guard->check()) {
                // empty introspect to get redirect URL from SSO
                $this->sso->introspect(null);
            }

            // check whether token is still valid
            $this->sso->introspect($token);
        } catch (SsoExpiredException $tokenExpired) {
            try {
                if ($request->wantsJson()) {
                    throw new AuthenticationException($tokenExpired->getCode());
                }
                $tokenResponse = $this->sso->refresh($token);
                $this->guard->setToken($tokenResponse['token']);
            } catch (SsoExpiredException $refreshExpired) {
                $redirectUrl = Http::createFromString($tokenExpired->redirect);
                $query = Query::createFromPairs([
                    'successUrl' => $request->fullUrl(),
                    'errorUrl' => config('services.remp_sso.error_url') ?: route('sso.error'),
                ])->getContent() ?: '';
                return redirect($redirectUrl->withQuery($query)->__toString());
            }
        }

        return $next($request);
    }

    /**
     * handleCallback processes request when returning back from SSO with a token. It gets user data via introspect
     * call, stores them to Guard and returns RedirectResponse.
     *
     * @param \Illuminate\Http\Request $request
     * @return RedirectResponse
     */
    private function handleCallback($request)
    {
        $token = $request->query('token');
        $userArr = $this->sso->introspect($token);

        $user = new User();
        $user->id = $userArr['id'];
        $user->name = $userArr['name'];
        $user->email = $userArr['email'];
        $user->scopes = $userArr['scopes'];

        $this->guard->setUser($user);
        $this->guard->setToken($token);

        // now get rid of token param once handled

        $fullUrl = Http::createFromString($request->fullUrl());
        $url = Http::createFromString($request->url());

        $queryPairs = Query::parse($fullUrl->getQuery());
        unset($queryPairs['token']);
        $query = Query::createFromPairs($queryPairs)->getContent() ?: '';

        return redirect($url->withQuery($query)->__toString());
    }
}
