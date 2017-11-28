<?php

namespace App\Http\Middleware;

use Closure;

class JsonApiMiddleware
{
    const PARSED_METHODS = [
        'POST', 'PUT', 'PATCH'
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (in_array($request->getMethod(), self::PARSED_METHODS)) {
            $request->merge((array)json_decode($request->getContent()));
        }

        return $next($request);
    }
}