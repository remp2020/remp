<?php

namespace Remp\LaravelSso\Http\Middleware;

use Closure;
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
        try {
            if ($request->has('token')) {
                // handle token param

                $token = $request->query('token');
                $userArr = $this->sso->introspect($token);

                $user = new User();
                $user->id = $userArr['id'];
                $user->name = $userArr['name'];
                $user->email = $userArr['email'];
                $user->scopes = $userArr['scopes'];

                $this->guard->setUser($user);

                // get rid of token param once handled

                $fullUrl = Http::createFromString($request->fullUrl());
                $url = Http::createFromString($request->url());

                $queryPairs = Query::parse($fullUrl->getQuery());
                unset($queryPairs['token']);
                $query = Query::createFromPairs($queryPairs)->getContent() ?: '';

                return redirect($url->withQuery($query)->__toString());
            } else {
                if (!$this->guard->check()) {
                    $this->sso->introspect(null);
                }
            }
        } catch (SsoExpiredException $e) {
            $redirectUrl = Http::createFromString($e->redirect);
            $query = Query::createFromPairs([
                'successUrl' => $request->fullUrl(),
                'errorUrl' => config('services.remp_sso.error_url', route('sso.error')),
            ])->getContent() ?: '';
            return redirect($redirectUrl->withQuery($query)->__toString());
        }

        return $next($request);
    }
}