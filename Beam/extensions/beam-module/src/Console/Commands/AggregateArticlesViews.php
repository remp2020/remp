<?php

namespace Remp\BeamModule\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Remp\BeamModule\Console\Command;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\ArticleAggregatedView;
use Remp\BeamModule\Model\ViewsPerBrowserMv;
use Remp\BeamModule\Model\ViewsPerUserMv;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;

class AggregateArticlesViews extends Command
{
    const KEY_SEPARATOR = '||||';
    const COMMAND = 'pageviews:aggregate-articles-views';
    const TIMESPENT_IGNORE_THRESHOLD_SECS = 3600;

    // TODO remove skip-temp-aggregation after temp aggregation tables are no longer used
    protected $signature = self::COMMAND . ' 
        {--date= : Date you want to aggregate.} 
        {--date-from= : Start of a date range you want to aggregate. Needs to be combined with --date-to.}
        {--date-to= : End of a date range you want to aggregate. Needs to be combined with --date-from.}
        {--step=40 : Time frame (in minutes) which aggregation should use to request the date. Bigger the traffic, smaller the window. If the time-frame is too broad, it might cause Segments API to return an error due to the amount of buckets necessary to generate in Elasticsearch.}
        {--skip-temp-aggregation}';

    protected $description = 'Aggregate pageviews and time spent data for each user and article for yesterday';

    private $journalContract;

    public function __construct(JournalContract $journalContract)
    {
        parent::__construct();
        // Client in Journal uses exponential back-off retry strategy, see service provider for details
        $this->journalContract = $journalContract;
    }

    public function handle()
    {
        $this->line('');
        $this->line('<info>***** Aggregatting article views *****</info>');
        $this->line('');

        $step = filter_var($this->option('step'), FILTER_VALIDATE_INT);
        if (!$step) {
            $this->error('Invalid --step option (expecting integer): ' . $this->option('step'));
            return self::FAILURE;
        }

        // TODO set this up depending finalized conditions
        // First delete data older than 30 days
        $dateThreshold = Carbon::today()->subDays(30)->toDateString();
        ArticleAggregatedView::where('date', '<=', $dateThreshold)->delete();

        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        if ($dateFrom || $dateTo) {
            if (!$dateFrom) {
                $this->error('Missing --date-from option');
                return self::FAILURE;
            }
            if (!$dateTo) {
                $this->error('Missing --date-to option');
                return self::FAILURE;
            }

            $from = Carbon::parse($dateFrom)->startOfDay();
            $to = Carbon::parse($dateTo)->startOfDay();

            while ($from->lte($to)) {
                $this->aggregateDay($from, $step);
                $from->addDay();
            }
        } else {
            $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
            $this->aggregateDay($date, $step);
        }

        // Update 'materialized view' to test author segments conditions
        // TODO only temporary, remove this after conditions are finalized
        if (!$this->option('skip-temp-aggregation')) {
            $this->createTemporaryAggregations();
        }

        $this->line(' <info>OK!</info>');
        return self::SUCCESS;
    }

    private function aggregateDay(Carbon $startDate, int $step)
    {
        ArticleAggregatedView::where('date', $startDate)->delete();

        // Aggregate pageviews and timespent data in time windows
        $timeWindowMinutes = $step; // in minutes
        $timeWindowsCount = 1440 / $timeWindowMinutes; // 1440 - number of minutes in day

        $timeAfter = $startDate;
        $timeBefore = (clone $timeAfter)->addMinutes($timeWindowMinutes);
        $date = $timeAfter->toDateString();

        for ($i = 0; $i < $timeWindowsCount; $i++) {
            [$data, $articleIds] = $this->aggregatePageviews([], [], $timeAfter, $timeBefore);
            [$data, $articleIds] = $this->aggregateTimespent($data, $articleIds, $timeAfter, $timeBefore);
            $this->storeData($data, $articleIds, $date);

            $timeAfter = $timeAfter->addMinutes($timeWindowMinutes);
            $timeBefore = $timeBefore->addMinutes($timeWindowMinutes);
        }
    }

    private function createTemporaryAggregations()
    {
        $days30ago = Carbon::today()->subDays(30);
        $days60ago = Carbon::today()->subDays(60);
        $days90ago = Carbon::today()->subDays(90);

        ViewsPerBrowserMv::query()->delete();
        $this->aggregateViewsPer('browser', 'total_views_last_30_days', $days30ago);
        $this->aggregateViewsPer('browser', 'total_views_last_60_days', $days60ago);
        $this->aggregateViewsPer('browser', 'total_views_last_90_days', $days90ago);

        ViewsPerUserMv::query()->delete();
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

            $sql = <<<SQL
    INSERT INTO $tableToUpdate ($groupParameter, $daysColumn) 
    SELECT $groupParameter, sum(pageviews) 
    FROM article_aggregated_views
    JOIN article_author ON article_author.article_id = article_aggregated_views.article_id
    WHERE timespent <= 3600 AND date >= ? and date <= ? 
SQL;

            DB::insert($sql . ($groupBy === 'user' ? "and user_id <> ''" : '') . " group by $groupParameter
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
        $request = new AggregateRequest('pageviews', 'timespent');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id', 'user_id', 'browser_id');

        $records = collect($this->journalContract->sum($request));

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
            $sum = (int) $record->sum;
            if ($sum >= self::TIMESPENT_IGNORE_THRESHOLD_SECS) {
                continue;
            }

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
        $request = new AggregateRequest('pageviews', 'load');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id', 'user_id', 'browser_id');

        $records = collect($this->journalContract->count($request));
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

        $articleIdMap = Article::whereIn(
            'external_id',
            array_map('strval', array_keys($articleIds))
        )->pluck('id', 'external_id');

        $items = [];
        foreach ($data as $key => $articlesData) {
            [$browserId, $userId] = explode(self::KEY_SEPARATOR, $key, 2);

            foreach ($articlesData as $externalArticleId => $record) {
                if (!isset($articleIdMap[$externalArticleId])) {
                    continue;
                }
                $items[] = [
                    'article_id' => $articleIdMap[$externalArticleId],
                    'browser_id' => $browserId === '' ? null : $browserId,
                    'user_id' => $userId === '' ? null : $userId,
                    'date' => $date,
                    'pageviews' => $record['pageviews'],
                    'timespent' => $record['timespent']
                ];
            }
            $bar->advance();
        }

        foreach (array_chunk($items, 500) as $itemsChunk) {
            ArticleAggregatedView::insertOnDuplicateKey($itemsChunk, [
                'pageviews' => DB::raw('pageviews + VALUES(pageviews)')->getValue(DB::connection()->getQueryGrammar()),
                'timespent' => DB::raw('timespent + VALUES(timespent)')->getValue(DB::connection()->getQueryGrammar()),
            ]);
        }

        $bar->finish();
        $this->line(' <info>OK!</info>');
    }
}
