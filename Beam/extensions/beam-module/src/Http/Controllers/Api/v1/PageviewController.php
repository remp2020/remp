<?php

namespace Remp\BeamModule\Http\Controllers\Api\v1;

use Remp\BeamModule\Http\Requests\Api\v1\PageviewsTimeHistogramRequest;
use Remp\BeamModule\Model\Pageviews\Api\v1\TimeHistogram;

class PageviewController
{
    public function timeHistogram(PageviewsTimeHistogramRequest $request, TimeHistogram $timeHistogram)
    {
        return response()->json($timeHistogram->getTimeHistogram($request));
    }
}
