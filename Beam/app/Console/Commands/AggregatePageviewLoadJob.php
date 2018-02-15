<?php

namespace App\Console\Commands;

use App\ArticlePageviews;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregatePageviewLoadJob extends Command
{
    protected $signature = 'aggregate:pageview-load';

    protected $description = 'Reads pageview/load data from journal and stores aggregated data';

    public function handle(JournalContract $journalContract)
    {
        $timeBefore = Carbon::now()->minute(0)->second(0);
        $timeAfter = (clone $timeBefore)->subHour();

        $request = new JournalAggregateRequest('pageviews', 'load');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id');

        $this->line(sprintf("Fetching aggregated pageviews data from <info>%s</info> to <info>%s</info>.", $timeAfter->toDateTimeString(), $timeBefore->toDateTimeString()));

        $records = $journalContract->count($request);

        if (count($records) === 1 && !isset($records[0]->tags->article_id)) {
            $this->line(sprintf("No articles to process, exiting."));
            return;
        }

        foreach ($records as $record) {
            $this->line(sprintf("Processing article pageviews: <info>%s</info>", $record->tags->article_id));

            $ap = new ArticlePageviews();
            $ap->article_id = $record->tags->article_id;
            $ap->sum = $record->count;
            $ap->time_from = $timeAfter;
            $ap->time_to = $timeBefore;
            $ap->save();
        }
    }
}
