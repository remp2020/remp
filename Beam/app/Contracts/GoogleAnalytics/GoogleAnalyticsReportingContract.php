<?php

namespace App\Contracts\GoogleAnalytics;

use Illuminate\Support\Collection;

interface GoogleAnalyticsReportingContract
{
    public function report(GoogleAnalyticsReportingRequest $request): Collection;
}
