<?php

namespace App\Console\Commands;

use App\Article;
use App\ArticlePageviews;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregatePageviewLoadJob extends Command
{
    protected $signature = 'pageviews:aggregate-load {--now=}';

    protected $description = 'Reads pageview/load data from journal and stores aggregated data';

    public function handle(JournalContract $journalContract)
    {
        $now = $this->hasOption('now') ? Carbon::parse($this->option('now')) : Carbon::now();
        $timeBefore = $now->minute(0)->second(0);
        $timeAfter = (clone $timeBefore)->subHour();

        $request = new JournalAggregateRequest('pageviews', 'load');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id');

        $this->line(sprintf("Fetching aggregated pageviews data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));

        $records = $journalContract->count($request);

        if (count($records) === 1 && !isset($records[0]->tags->article_id)) {
            $this->line(sprintf("No articles to process, exiting."));
            return;
        }

        foreach ($records as $record) {
            if (empty($record->tags->article_id)) {
                continue;
            }
            $this->line(sprintf("Processing article pageviews: <info>%s</info>", $record->tags->article_id));

            $article = Article::select()->where([
                'external_id' => $record->tags->article_id,
            ])->first();

            if (!$article) {
                // article not inserted to beam
                continue;
            }

            /** @var ArticlePageviews $ap */
            $ap = ArticlePageviews::firstOrNew([
                'article_id' => $article->id,
                'time_from' => $timeAfter,
                'time_to' => $timeBefore,
            ]);
            $ap->sum = $record->count;
            $ap->save();

            $ap->article->pageview_sum = $ap->article->pageviews()->sum('sum');
            $ap->article->save();
        }
    }
}
