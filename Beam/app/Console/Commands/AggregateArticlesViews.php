<?php

namespace App\Console\Commands;

use App\Article;
use App\ArticleAggregatedView;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use App\ViewsPerBrowserMv;
use App\ViewsPerUserMv;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateArticlesViews extends Command
{
    const KEY_SEPARATOR = '||||';
    const COMMAND = 'pageviews:aggregate-articles-views';

    protected $signature = self::COMMAND . ' {--date=}';

    protected $description = 'Aggregate pageviews and time spent data for each user and article for yesterday';

    private $journalContract;

    public function __construct(JournalContract $journalContract)
    {
        parent::__construct();
        $this->journalContract = $journalContract;
    }

    public function handle()
    {
        Log::debug('AggregateArticlesViews job STARTED');

        // TODO set this up depending finalized conditions
        // First delete data older than 90 days
        $dateThreshold = Carbon::today()->subDays(90)->toDateString();
        ArticleAggregatedView::where('date', '<=', $dateThreshold)->delete();

        // Aggregate pageviews and timespent data in time windows
        $timeWindowMinutes = 30; // in minutes
        $timeWindowsCount = 1440 / $timeWindowMinutes; // 1440 - number of minutes in day
        $startDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $timeAfter = $startDate;
        $timeBefore = (clone $timeAfter)->addMinutes($timeWindowMinutes);
        $date = $timeAfter->toDateString();
        for ($i = 0; $i < $timeWindowsCount; $i++) {
            list($data, $articleIds) = $this->aggregatePageviews([], [], $timeAfter, $timeBefore);
            list($data, $articleIds) = $this->aggregateTimespent($data, $articleIds, $timeAfter, $timeBefore);
            $this->storeData($data, $articleIds, $date);

            $timeAfter = $timeAfter->addMinutes($timeWindowMinutes);
            $timeBefore = $timeBefore->addMinutes($timeWindowMinutes);
        }

        // Update 'materialized view' to test author segments conditions
        // See CreateAuthorSegments task
        // TODO only temporary, remove this after conditions are finalized
        $this->createTemporaryAggregations();

        Log::debug('AggregateArticlesViews job FINISHED');
    }

    private function createTemporaryAggregations()
    {
        $days30ago = Carbon::today()->subDays(30);
        $days60ago = Carbon::today()->subDays(60);
        $days90ago = Carbon::today()->subDays(90);

        ViewsPerBrowserMv::truncate();
        $this->aggregateViewsPer('browser', 'total_views_last_30_days', $days30ago);
        $this->aggregateViewsPer('browser', 'total_views_last_60_days', $days60ago);
        $this->aggregateViewsPer('browser', 'total_views_last_90_days', $days90ago);

        ViewsPerUserMv::truncate();
        $this->aggregateViewsPer('user', 'total_views_last_30_days', $days30ago);
        $this->aggregateViewsPer('user', 'total_views_last_60_days', $days60ago);
        $this->aggregateViewsPer('user', 'total_views_last_90_days', $days90ago);
    }

    private function aggregateViewsPer($groupBy, $daysColumn, Carbon $daysAgo)
    {
        if ($groupBy === 'browser') {
            $tableToUpdate = 'views_per_browser_mv';
            $groupParameter = 'browser_id';
        } else {
            $tableToUpdate = 'views_per_user_mv';
            $groupParameter = 'user_id';
        }

        $today = Carbon::today();
        // aggregate values in 14-days windows to avoid filling all the RAM while querying DB
        $daysWindow = 14;

        $startDate = clone $daysAgo;
        while ($startDate->lessThan($today)) {
            $end = (clone $startDate)->addDays($daysWindow - 1)->toDateString();
            $start = $startDate->toDateString();

            DB::insert("insert into $tableToUpdate ($groupParameter, $daysColumn) 
select $groupParameter, sum(pageviews) from article_aggregated_views
join article_author on article_author.article_id = article_aggregated_views.article_id
where timespent <= 3600 and date >= ? and date <= ? " . ($groupBy === 'user' ? "and user_id <> ''" : '') . " group by $groupParameter
ON DUPLICATE KEY UPDATE $daysColumn = $daysColumn + VALUES(`$daysColumn`)", [$start, $end]);

            $startDate->addDays($daysWindow);
        }
    }

    /**
     * @param array $data
     * @param array $articleIds
     * @param $timeAfter
     * @param $timeBefore
     * @return array {
     *   @type array $data parsed data appended to provided $data param
     *   @type array $articleIds list of touched articleIds appended to provided $articleIds param
     * }
     *
     */
    private function aggregateTimespent(array $data, array $articleIds, $timeAfter, $timeBefore): array
    {
        $this->line(sprintf("Fetching aggregated <info>timespent</info> data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));
        $request = new JournalAggregateRequest('pageviews', 'timespent');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id', 'user_id', 'browser_id');

        $records = $this->journalContract->sum($request);

        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return [$data, $articleIds];
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

            if (!array_key_exists($key, $data)) {
                $data[$key] = [];
            }
            if (!array_key_exists($articleId, $data[$key])) {
                $data[$key][$articleId] = [
                    'pageviews' => 0,
                    'timespent' => 0
                ];
            }

            $data[$key][$articleId]['timespent'] += $sum;
            $articleIds[$articleId] = true;
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');

        return [$data, $articleIds];
    }

    /**
     * @param array $data
     * @param array $articleIds
     * @param $timeAfter
     * @param $timeBefore
     * @return array {
     *   @type array $data parsed data appended to provided $data param
     *   @type array $articleIds list of touched articleIds appended to provided $articleIds param
     * }
     *
     */
    private function aggregatePageviews(array $data, array $articleIds, $timeAfter, $timeBefore): array
    {
        $this->line(sprintf("Fetching aggregated <info>pageviews</info> data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));
        $request = new JournalAggregateRequest('pageviews', 'load');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id', 'user_id', 'browser_id');

        $records = $this->journalContract->count($request);
        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process."));
            return [$data, $articleIds];
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

            if (!array_key_exists($key, $data)) {
                $data[$key] = [];
            }
            if (!array_key_exists($articleId, $data[$key])) {
                $data[$key][$articleId] = [
                    'pageviews' => 0,
                    'timespent' => 0
                ];
            }

            $data[$key][$articleId]['pageviews'] += $count;
            $articleIds[$articleId] = true;
            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');

        return [$data, $articleIds];
    }

    private function storeData(array $data, array $articleIds, string $date)
    {
        if (empty($data)) {
            return;
        }

        $bar = $this->output->createProgressBar(count($data));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Storing aggregated data');

        $articleIdMap = Article::whereIn('external_id', array_keys($articleIds))->pluck('id', 'external_id');

        $items = [];
        foreach ($data as $key => $articlesData) {
            list($browserId, $userId) = explode(self::KEY_SEPARATOR, $key, 2);

            foreach ($articlesData as $externalArticleId => $record) {
                if (!isset($articleIdMap[$externalArticleId])) {
                    continue;
                }
                $items[] = [
                    'article_id' => $articleIdMap[$externalArticleId],
                    'browser_id' => $browserId,
                    'user_id' => $userId,
                    'date' => $date,
                    'pageviews' => $record['pageviews'],
                    'timespent' => $record['timespent']
                ];
            }
            $bar->advance();
        }

        foreach (array_chunk($items, 500) as $itemsChunk) {
            ArticleAggregatedView::insertOnDuplicateKey($itemsChunk, [
                'pageviews' => DB::raw('pageviews + VALUES(pageviews)'),
                'timespent' => DB::raw('timespent + VALUES(timespent)'),
            ]);
        }

        $bar->finish();
        $this->line(' <info>OK!</info>');
    }
}
