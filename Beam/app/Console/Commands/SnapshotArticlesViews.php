<?php

namespace App\Console\Commands;

use App\Helpers\Journal\JournalHelpers;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Remp\Journal\JournalContract;

class SnapshotArticlesViews extends Command
{
    const COMMAND = 'pageviews:snapshot';

    protected $signature = self::COMMAND . ' {--time=}';

    protected $description = 'Snapshot current traffic data (rounded to minutes) from concurrents segment index';

    public function __construct(JournalContract $journal)
    {
        parent::__construct();
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
    }

    public function handle()
    {
        $thisMinute = Carbon::now()->second(0);

        if ($this->hasOption('time')) {
            $thisMinute = Carbon::parse($this->option('time'))->second(0);
        }

        $this->line('');
        $this->line("<info>***** Snapshotting traffic data for $thisMinute *****</info>");
        $this->line('');


        $this->snapshot($thisMinute);

        $this->line(' <info>OK!</info>');
    }

    private function snapshot(Carbon $thisMinute)
    {

    }
}
