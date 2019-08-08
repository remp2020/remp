<?php

namespace App\Console\Commands;

use App\Helpers\Journal\JournalHelpers;
use App\Model\ArticleViewsSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Remp\Journal\ConcurrentsRequest;
use Remp\Journal\JournalContract;

class SnapshotArticlesViews extends Command
{
    const COMMAND = 'pageviews:snapshot';

    protected $signature = self::COMMAND . ' {--time=}';

    protected $description = 'Snapshot current traffic data (rounded to minutes) from concurrents segment index';

    private $journal;

    private $journalHelper;

    public function __construct(JournalContract $journal)
    {
        parent::__construct();
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
    }

    public function handle()
    {
        $thisMinute = Carbon::now();

        if ($this->hasOption('time')) {
            $thisMinute = Carbon::parse($this->option('time'));
        }

        $this->line('');
        $this->line("<info>***** Snapshotting traffic data for $thisMinute *****</info>");
        $this->line('');

        $this->snapshot($thisMinute);

        $this->line(' <info>OK!</info>');
    }

    private function snapshot(Carbon $now)
    {
        $to = $now;
        $from = (clone $to)->subSeconds(600); // Last 10 minutes

        $request = new ConcurrentsRequest();
        $request->setTimeAfter($from);
        $request->setTimeBefore($to);
        $request->addGroup('article_id', 'derived_referer_medium', 'explicit_referer_medium', 'token');

        $items = [];

        $externalIds = [];

        $dbTime = $to->second(0);

        foreach ($this->journal->concurrents($request) as $record) {
            $token = $record->tags->token;
            $articleId = $record->tags->article_id;
            $derivedRefererMedium = $record->tags->derived_referer_medium;
            $explicitRefererMedium = $record->tags->explicit_referer_medium;

            $key = self::key($token, $articleId, $derivedRefererMedium, $explicitRefererMedium);

            $items[$key] = [
                'time' => $dbTime,
                'property_token' => $token,
                'external_article_id' => $articleId,
                'derived_referer_medium' => $derivedRefererMedium,
                'explicit_referer_medium' => $explicitRefererMedium,
                'count' => $record->count,
                'count_by_referer' => '{}',
            ];

            if ($derivedRefererMedium === 'external') {
                $externalIds[] = $articleId;
            }
        }

        // Load sources count for each external referer
        // TODO temporarily disabled loading of external referers
        //foreach (array_chunk($externalIds, 100) as $externalIdsChunk) {
        //    $r = new ConcurrentsRequest();
        //    $r->setTimeAfter($from);
        //    $r->setTimeBefore($to);
        //    $r->addFilter('article_id', ...$externalIdsChunk);
        //    $r->addFilter('derived_referer_medium', 'external');
        //    $r->addGroup('article_id', 'token', 'explicit_referer_medium', 'derived_referer_host_with_path');
        //
        //
        //    $referers = [];
        //    foreach ($this->journal->concurrents($r) as $record) {
        //        $articleId = $record->tags->article_id;
        //        $token = $record->tags->token;
        //        $explicitRefererMedium = $record->tags->explicit_referer_medium;
        //        $host = $record->tags->derived_referer_host_with_path;
        //
        //        if (!array_key_exists($token, $referers)) {
        //            $referers[$token] = [];
        //        }
        //        if (!array_key_exists($articleId, $referers[$token])) {
        //            $referers[$token][$articleId] = [];
        //        }
        //        if (!array_key_exists($explicitRefererMedium, $referers[$token][$articleId])) {
        //            $referers[$token][$articleId][$explicitRefererMedium] = [];
        //        }
        //
        //        $referers[$token][$articleId][$explicitRefererMedium][$host] = $record->count;
        //    }
        //
        //
        //    foreach ($referers as $token => $tokenReferers) {
        //        foreach ($tokenReferers as $articleId => $articleReferers) {
        //            foreach ($articleReferers as $explicitRefererMedium => $mediumReferers) {
        //                $key = self::key($token, $articleId, 'external', $explicitRefererMedium);
        //                if (array_key_exists($key, $items)) {
        //                    $items[$key]['count_by_referer'] = json_encode($mediumReferers);
        //                }
        //            }
        //        }
        //    }
        //}

        // Save
        ArticleViewsSnapshot::where('time', $dbTime)->delete();
        
        foreach (array_chunk($items, 100) as $itemsChunk) {
            ArticleViewsSnapshot::insert($itemsChunk);
            $count = count($itemsChunk);
            $this->line("$count records inserted");
        }
    }

    private static function key($token, $articleId, $derivedRefererMedium, $explicitRefererMedium)
    {
        return "{$token}|||{$articleId}|||{$derivedRefererMedium}|||{$explicitRefererMedium}";
    }
}
