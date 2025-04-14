<?php

namespace Remp\LaravelSso\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use League\Uri\Components\Query;
use League\Uri\Http;
use League\Uri\QueryString;
use League\Uri\Uri;
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
                $redirectUrl = Uri::new($tokenExpired->redirect);

                $query = Query::new($redirectUrl->getQuery());
                $query = $query
                    ->appendTo('successUrl', $request->fullUrl())
                    ->appendTo('errorUrl', config('services.remp_sso.error_url') ?: route('sso.error'));

                return redirect($redirectUrl->withQuery($query->value()));
            }
        }

        return $next($request);
    }

    /**
     * handleCallback processes request when returning back from SSO with a token. It gets user data via introspect
     * call, stores them to Guard and returns RedirectResponse.
     */
    private function handleCallback(Request $request)
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

        $url = Uri::new($request->url());
        $query = Query::new($url->getQuery());

        return redirect($url->withQuery(
            query: $query->withoutPairByKey('token')
        ));
    }
}
