<?php

namespace App\Console\Commands;

use App\Article;
use App\ArticleUserView;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateUserArticles extends Command
{
    protected $signature = 'pageviews:aggregate-user-articles {--date=}';

    protected $description = 'Aggregate pageviews and time spent data for each user and article for yesterday';

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
        // First delete data older than 30 days
        $dateThreshold = Carbon::today()->subDays(30)->toDateString();
        ArticleUserView::where('date', '<=', $dateThreshold)->delete();

        // Aggregate last day pageviews in 1-hour windows
        $startDate = $this->hasOption('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $timeAfter = $startDate;
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
        $request->addGroup('article_id', 'user_id');

        $records = $this->journalContract->sum($request);

        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return;
        }

        $bar = $this->output->createProgressBar(count($records));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Processing timespent');

        foreach ($records as $record) {
            if (empty($record->tags->article_id) || empty($record->tags->user_id)) {
                $bar->advance();
                continue;
            }
            $articleId = $record->tags->article_id;
            $userId = $record->tags->user_id;
            $sum = $record->sum;

            if (!isset($this->pageviews[$userId][$articleId])) {
                // do not store timespent data if not a single pageview is recorded
                continue;
            }

            $this->pageviews[$userId][$articleId]['timespent'] += $sum;
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
        $request->addGroup('article_id', 'user_id');

        $records = $this->journalContract->count($request);
        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return;
        }

        $bar = $this->output->createProgressBar(count($records));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Processing pageviews');

        foreach ($records as $record) {
            if (empty($record->tags->article_id) || empty($record->tags->user_id)) {
                $bar->advance();
                continue;
            }
            $articleId = $record->tags->article_id;
            $userId = $record->tags->user_id;
            $count = $record->count;

            if (!array_key_exists($userId, $this->pageviews)) {
                $this->pageviews[$userId] = [];
            }
            if (!array_key_exists($articleId, $this->pageviews[$userId])) {
                $this->pageviews[$userId][$articleId] = [
                    'pageviews' => 0,
                    'timespent' => 0
                ];
            }

            $this->pageviews[$userId][$articleId]['pageviews'] += $count;
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');
    }

    private function storeData($date)
    {
        $bar = $this->output->createProgressBar(count($this->pageviews));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Storing aggregated data');

        $articleIdMap = [];

        foreach ($this->pageviews as $userId => $articlesData) {
            $items = [];
            foreach ($articlesData as $externalArticleId => $record) {
                $articleId = null;
                if (array_key_exists($externalArticleId, $articleIdMap)) {
                    $articleId = $articleIdMap[$externalArticleId];
                } else {
                    $article = Article::select()->where([
                        'external_id' => $externalArticleId,
                    ])->first();

                    if (!$article) {
                        continue;
                    }

                    $articleId = $article->id;
                    $articleIdMap[$externalArticleId] = $articleId;
                }

                $items[] = [
                    'article_id' => $articleId,
                    'user_id' => $userId,
                    'date' => $date,
                    'pageviews' => $record['pageviews'],
                    'timespent' => $record['timespent']
                ];
            }
            ArticleUserView::insertOnDuplicateKey($items, ['pageviews', 'timespent']);
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');
    }
}
