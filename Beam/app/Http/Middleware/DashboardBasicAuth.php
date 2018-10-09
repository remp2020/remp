<?php

namespace App\Http\Middleware;

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
        $requiredUsername = config('dashboard.username');
        $requiredPassword = config('dashboard.password');

        if ($request->getUser() === $requiredUsername
            && $request->getPassword() === $requiredPassword) {
            return $next($request);
        }
        throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
    }
}
