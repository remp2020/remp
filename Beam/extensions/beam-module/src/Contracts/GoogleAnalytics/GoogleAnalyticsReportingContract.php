<?php

namespace Remp\BeamModule\Contracts\GoogleAnalytics;

use Illuminate\Support\Collection;

interface GoogleAnalyticsReportingContract
{
    public function report(GoogleAnalyticsReportingRequest $request): Collection;
}
