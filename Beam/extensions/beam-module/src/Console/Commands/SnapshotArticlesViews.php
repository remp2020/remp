<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Helpers\Journal\JournalHelpers;
use Remp\BeamModule\Model\ArticleViewsSnapshot;
use Carbon\Carbon;
use Remp\BeamModule\Console\Command;
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
        return 0;
    }

    private function snapshot(Carbon $now)
    {
        $to = $now;

        $records = $this->journalHelper->currentConcurrentsCount(function (ConcurrentsRequest $req) {
            $req->addGroup('article_id', 'derived_referer_medium', 'token');
        }, $to);

        $items = [];
        $dbTime = $to->second(0)->microsecond(0);

        foreach ($records as $record) {
            $token = $record->tags->token;
            $articleId = $record->tags->article_id;
            $refererMedium = $record->tags->derived_referer_medium;

            $items[] = [
                'time' => $dbTime,
                'property_token' => $token,
                'external_article_id' => $articleId,
                'referer_medium' => $refererMedium,
                'count' => $record->count
            ];
        }

        // Save
        ArticleViewsSnapshot::where('time', $dbTime)->delete();
        
        foreach (array_chunk($items, 100) as $itemsChunk) {
            ArticleViewsSnapshot::insert($itemsChunk);
            $count = count($itemsChunk);
            $this->line("$count records inserted");
        }
    }
}
