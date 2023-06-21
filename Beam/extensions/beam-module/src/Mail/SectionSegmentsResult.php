<?php

namespace Remp\BeamModule\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SectionSegmentsResult extends Mailable
{
    use Queueable, SerializesModels;

    private $results;

    private $minimalViews;

    private $minimalAverageTimespent;

    private $minimalRatio;

    private $historyDays;

    public function __construct($results, $minimalViews, $minimalAverageTimespent, $minimalRatio, $historyDays)
    {
        $this->results = $results;
        $this->minimalViews = $minimalViews;
        $this->minimalAverageTimespent = $minimalAverageTimespent;
        $this->minimalRatio = $minimalRatio;
        $this->historyDays = $historyDays;
    }

    public function build()
    {
        return $this->view('sections.segments.results_email')
            ->with([
                'results' => $this->results,
                'minimal_views' => $this->minimalViews,
                'minimal_average_timespent' => $this->minimalAverageTimespent,
                'minimal_ratio' => $this->minimalRatio,
                'history_days' => $this->historyDays
            ]);
    }
}
