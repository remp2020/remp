<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\PageviewsTimeHistogramRequest;
use App\Model\Pageviews\Api\v1\TimeHistogram;

class PageviewController
{
    public function timeHistogram(PageviewsTimeHistogramRequest $request, TimeHistogram $timeHistogram)
    {
        return response()->json($timeHistogram->getTimeHistogram($request));
    }
}
