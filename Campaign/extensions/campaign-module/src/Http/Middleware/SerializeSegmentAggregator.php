<?php

namespace Remp\CampaignModule\Http\Middleware;

use Remp\CampaignModule\Contracts\SegmentAggregator;
use Closure;

class SerializeSegmentAggregator
{
    private $segmentAggregator;

    public function __construct(SegmentAggregator $segmentAggregator)
    {
        $this->segmentAggregator = $segmentAggregator;
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
        if (strpos($request->path(), 'showtime') === false) {
            // serialize segment aggregator (required in showtime.php)
            $this->segmentAggregator->serializeToRedis();
        }

        return $next($request);
    }
}
