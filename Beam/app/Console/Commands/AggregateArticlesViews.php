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

    public function __construct(JournalContract $journalContract)
    {
        parent::__construct();
        $this->journalContract = $journalContract;
    }

    public function handle()
    {
        // First delete data older than 30 days
        $dateThreshold = Carbon::today()->subDays(30)->toDateString();
        ArticleAggregatedView::where('date', '<=', $dateThreshold)->delete();

        // Aggregate last day pageviews in 1-hour windows
        $startDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $timeAfter = $startDate;
        $timeBefore = (clone $timeAfter)->addHour()->subSecond();
        $date = $timeAfter->toDateString();
        for ($i = 0; $i < 24; $i++) {
            list($data, $articleIds) = $this->aggregatePageviews([], [], $timeAfter, $timeBefore);
            list($data, $articleIds) = $this->aggregateTimespent($data, $articleIds, $timeAfter, $timeBefore);
            $this->storeData($data, $articleIds, $date);

            $timeAfter = $timeAfter->addHour();
            $timeBefore = $timeBefore->addHour();
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
            return [];
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

            if (!isset($data[$key][$articleId])) {
                // do not store timespent data if not a single pageview is recorded
                continue;
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
            return [];
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
        ArticleAggregatedView::insertOnDuplicateKey($items, [
            'pageviews' => DB::raw('pageviews + ' . $record['pageviews']),
            'timespent' => DB::raw('timespent + ' . $record['timespent']),
        ]);
        $bar->finish();
        $this->line(' <info>OK!</info>');
    }
}
