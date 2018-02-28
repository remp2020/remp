<?php

namespace App\Console\Commands;

use App\Article;
use App\ArticleTimespent;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregatePageviewTimespentJob extends Command
{
    protected $signature = 'aggregate:pageview-timespent {--now=}';

    protected $description = 'Reads pageview/timespent data from journal and stores aggregated data';

    public function handle(JournalContract $journalContract)
    {
        $now = $this->hasOption('now') ? Carbon::parse($this->option('now')) : Carbon::now();
        $timeBefore = $now->minute(0)->second(0);
        $timeAfter = (clone $timeBefore)->subHour();

        $request = new JournalAggregateRequest('pageviews', 'timespent');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id');

        $this->line(sprintf("Fetching aggregated pageviews data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));

        $records = $journalContract->sum($request);

        if (count($records) === 1 && !isset($records[0]->tags->article_id)) {
            $this->line(sprintf("No articles to process, exiting."));
            return;
        }

        foreach ($records as $record) {
            $this->line(sprintf("Processing article pageviews: <info>%s</info>", $record->tags->article_id));

            $article = Article::select()->where([
                'external_id' => $record->tags->article_id,
            ])->first();

            /** @var ArticleTimespent $ap */
            $ap = ArticleTimespent::firstOrNew([
                'article_id' => $article->id,
                'time_from' => $timeAfter,
                'time_to' => $timeBefore,
            ]);
            $ap->sum = $record->sum;
            $ap->save();

            $ap->article->timespent_sum = $ap->article->timespent()->sum('sum');
            $ap->article->save();
        }
    }
}
