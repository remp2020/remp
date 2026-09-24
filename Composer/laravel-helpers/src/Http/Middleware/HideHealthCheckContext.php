<?php

namespace Remp\LaravelHelpers\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class HideHealthCheckContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (config('app.debug') || !$response instanceof JsonResponse) {
            return $response;
        }

        $body = $response->getData(true);
        $contexts = [];
        foreach ($body as $check => $entry) {
            if (isset($entry['context'])) {
                $contexts[$check] = $entry['context'];
                unset($body[$check]['context']);
            }
        }

        if (!$contexts) {
            return $response;
        }

        try {
            Log::warning('Health check context hidden from response', $contexts);
        } catch (Throwable) {
            // logging itself might be the failing check; never break the health endpoint
        }

        return $response->setData($body);
    }
}
