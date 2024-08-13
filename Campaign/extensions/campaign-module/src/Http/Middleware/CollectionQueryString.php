<?php

namespace Remp\CampaignModule\Http\Middleware;

use Remp\CampaignModule\CampaignCollection;
use Closure;
use Illuminate\Http\Request;

class CollectionQueryString
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->filled('collection')) {
            $collection = CampaignCollection::where('id', $request->collection)->firstOrFail();
            $request->route()?->setParameter('collection', $collection);
        }

        return $next($request);
    }
}
