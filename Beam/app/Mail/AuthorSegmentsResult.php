<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuthorSegmentsResult extends Mailable
{
    use Queueable, SerializesModels;

    private $results;

    private $minimalViews;

    private $minimalAverageTimespent;

    private $minimalRatio;

    private $historyDays;

    public function __construct($results, $minimalViews, $minimalAverageTimespent, $minimalRatio, $historyDays)
    {
        //
        $this->results = $results;
        $this->minimalViews = $minimalViews;
        $this->minimalAverageTimespent = $minimalAverageTimespent;
        $this->minimalRatio = $minimalRatio;
        $this->historyDays = $historyDays;
    }

    public function build()
    {
        return $this->view('test.results_email');
    }
}
