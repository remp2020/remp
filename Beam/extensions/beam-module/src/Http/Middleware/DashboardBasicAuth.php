<?php

namespace Remp\BeamModule\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class DashboardBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function handle($request, Closure $next)
    {
        // temporarily support 2 passwords
        $credentials = [
            config('dashboard.username') => config('dashboard.password'),
            config('dashboard.username2') => config('dashboard.password2'),
        ];
        unset($credentials[null]);
        unset($credentials['']);

        if (array_key_exists($request->getUser(), $credentials)
            && $credentials[$request->getUser()] === $request->getPassword()) {
            return $next($request);
        }
        throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
    }
}
