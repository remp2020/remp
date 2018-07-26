<?php

namespace App\Console\Commands;

use App\Article;
use App\ArticleAggregatedView;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateArticlesViews extends Command
{
    const KEY_SEPARATOR = '||||';
    const COMMAND = 'pageviews:aggregate-articles-views';

    protected $signature = self::COMMAND . ' {--date=}';

    protected $description = 'Aggregate pageviews and time spent data for each user and article for yesterday';

    private $journalContract;

    private $data;

    public function __construct(JournalContract $journalContract)
    {
        parent::__construct();
        $this->journalContract = $journalContract;
        $this->data = [];
    }

    public function handle()
    {
        // aggregating one full day may exceed default memory limit, therefore increase memory limit
        // typical memory usage for 1day aggregation ~130MB
        ini_set('memory_limit', '512M');

        // First delete data older than 30 days
        $dateThreshold = Carbon::today()->subDays(30)->toDateString();
        ArticleAggregatedView::where('date', '<=', $dateThreshold)->delete();

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
        $request->addGroup('article_id', 'user_id', 'browser_id');

        $records = $this->journalContract->sum($request);

        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return;
        }

        $bar = $this->output->createProgressBar(count($records));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Processing timespent');

        foreach ($records as $record) {
            $articleId = $record->tags->article_id;
            $userId = $record->tags->user_id ?? '';
            $browserId = $record->tags->browser_id ?? '';
            if (empty($articleId) || (empty($userId) && empty($browserId))) {
                $bar->advance();
                continue;
            }

            $sum = $record->sum;
            $key = $browserId . self::KEY_SEPARATOR . $userId;

            if (!isset($this->data[$key][$articleId])) {
                // do not store timespent data if not a single pageview is recorded
                continue;
            }

            $this->data[$key][$articleId]['timespent'] += $sum;
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
        $request->addGroup('article_id', 'user_id', 'browser_id');

        $records = $this->journalContract->count($request);
        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return;
        }

        $bar = $this->output->createProgressBar(count($records));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Processing pageviews');

        foreach ($records as $record) {
            $articleId = $record->tags->article_id;
            $userId = $record->tags->user_id ?? '';
            $browserId = $record->tags->browser_id ?? '';
            if (empty($articleId) || (empty($userId) && empty($browserId))) {
                $bar->advance();
                continue;
            }

            $key = $browserId . self::KEY_SEPARATOR . $userId;

            $count = $record->count;

            if (!array_key_exists($key, $this->data)) {
                $this->data[$key] = [];
            }
            if (!array_key_exists($articleId, $this->data[$key])) {
                $this->data[$key][$articleId] = [
                    'pageviews' => 0,
                    'timespent' => 0
                ];
            }

            $this->data[$key][$articleId]['pageviews'] += $count;
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');
    }

    private function storeData($date)
    {
        $bar = $this->output->createProgressBar(count($this->data));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Storing aggregated data');

        $articleIdMap = [];

        foreach ($this->data as $key => $articlesData) {
            $items = [];

            list($browserId, $userId) = explode(self::KEY_SEPARATOR, $key, 2);

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
                    'browser_id' => $browserId,
                    'user_id' => $userId,
                    'date' => $date,
                    'pageviews' => DB::raw('pageviews + ' . $record['pageviews']),
                    'timespent' => DB::raw('timespent + ' . $record['timespent']),
                ];
            }
            ArticleAggregatedView::insertOnDuplicateKey($items, ['pageviews', 'timespent']);
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');
    }
}
