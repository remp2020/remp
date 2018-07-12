<?php

namespace App\Console\Commands;

use App\Article;
use App\ArticleBrowserView;
use App\ArticlePageviews;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateUserArticlesJob extends Command
{
    protected $signature = 'pageviews:aggregate-user-articles';

    protected $description = 'Aggregate pageviews and time spent data for each user and article on daily bases.';

    private $journalContract;

    private $pageviews;

    public function __construct(JournalContract $journalContract)
    {
        parent::__construct();
        $this->journalContract = $journalContract;
        $this->pageviews = [];

    }

    public function handle()
    {
        // Aggregate last day pageviews in 1-hour windows
        $timeAfter = Carbon::yesterday();
        $timeBefore = (clone $timeAfter)->addHour()->subSecond();
        $date = $timeAfter->toDateString();
        for ($i = 0; $i < 24; $i++) {
            $this->aggregatePageviews($timeAfter, $timeBefore);
            $this->aggregateTimespent($timeAfter, $timeBefore);
            $timeAfter = $timeAfter->addHour();
            $timeBefore = $timeBefore->addHour();
        }
        $this->storeData($date);
    }

    private function aggregateTimespent($timeAfter, $timeBefore)
    {
        $this->line(sprintf("Fetching aggregated timespent data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));
        $request = new JournalAggregateRequest('pageviews', 'timespent');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addFilter('signed_in', 'true');
        $request->addGroup('article_id', 'browser_id');

        $records = $this->journalContract->sum($request);
        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return;
        }

        $bar = $this->output->createProgressBar(count($records));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Processing timespent');

        foreach ($records as $record) {
            if (empty($record->tags->article_id)) {
                $bar->advance();
                continue;
            }
            $articleId = $record->tags->article_id;
            $browserId = $record->tags->browser_id;
            $sum = $record->sum;

            if (!isset($this->pageviews[$browserId][$articleId])){
                // do not store timespent data if not a single pageview is recorded
                continue;
            }

            $this->pageviews[$browserId][$articleId]['timespent'] += $sum;
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');
    }

    private function aggregatePageviews($timeAfter, $timeBefore)
    {
        $this->line(sprintf("Fetching aggregated pageviews data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));
        $request = new JournalAggregateRequest('pageviews', 'load');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addFilter('signed_in', 'true');
        $request->addGroup('article_id', 'browser_id');

        $records = $this->journalContract->count($request);
        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return;
        }

        $bar = $this->output->createProgressBar(count($records));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Processing pageviews');

        foreach ($records as $record) {
            if (empty($record->tags->article_id)) {
                $bar->advance();
                continue;
            }
            $articleId = $record->tags->article_id;
            $browserId = $record->tags->browser_id;
            $count = $record->count;

            if (!array_key_exists($browserId, $this->pageviews)) {
                $this->pageviews[$browserId] = [];
            }
            if (!array_key_exists($articleId, $this->pageviews[$browserId])) {
                $this->pageviews[$browserId][$articleId] = [
                    'pageviews' => 0,
                    'timespent' => 0
                ];
            }

            $this->pageviews[$browserId][$articleId]['pageviews'] += $count;
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');
    }

    private function storeData($date)
    {
        foreach ($this->pageviews as $browserId => $articlesData) {
            $items = [];
            foreach ($articlesData as $articleId => $record) {
                $items[] = [
                    'article_id' => $articleId,
                    'browser_id' => $browserId,
                    'date' => $date,
                    'pageviews' => $record['pageviews'],
                    'timespent' => $record['timespent']
                ];
            }
            ArticleBrowserView::insert($items);
        }
    }
}
