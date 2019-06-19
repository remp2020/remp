<?php

namespace App\Console\Commands;

use App\Article;
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
        $thisMinute = Carbon::now()->second(0);

        if ($this->hasOption('time')) {
            $thisMinute = Carbon::parse($this->option('time'))->second(0);
        }

        $this->line('');
        $this->line("<info>***** Snapshotting traffic data for $thisMinute *****</info>");
        $this->line('');

        $this->snapshot($thisMinute);

        $this->line(' <info>OK!</info>');
    }

    private function snapshot(Carbon $thisMinute)
    {

        $to = $thisMinute;
        $from = (clone $to)->subSeconds(600); // Last 10 minutes

        $request = new ConcurrentsRequest();
        $request->setTimeAfter($from);
        $request->setTimeBefore($to);
        $request->addGroup('article_id', 'derived_referer_medium', 'explicit_referer_medium', 'token');

        $items = [];
        $externalIds = [];

        foreach ($this->journal->concurrents($request) as $record) {
            $externalIds[] = $record->tags->article_id;
            $items[$record->tags->article_id] = [
                'time' => $to,
                'property_token' => $record->tags->token,
                'external_article_id' => $record->tags->article_id,
                'article_id' => null,
                'derived_referer_medium' => $record->tags->derived_referer_medium,
                'explicit_referer_medium' => $record->tags->explicit_referer_medium,
                'count' => $record->count,
            ];
        }

        foreach (array_chunk($externalIds, 500) as $externalIdsChunk) {
            $articles = Article::whereIn('external_id', $externalIdsChunk)->select('id', 'external_id', 'title')->get();
            foreach ($articles as $article) {
                $items[$article->external_id]['article_id'] = $article->id;
            }
        }

        ArticleViewsSnapshot::where('time', $to)->delete();

        foreach (array_chunk($items, 500) as $itemsChunk) {
            ArticleViewsSnapshot::insert($itemsChunk);
            $count = count($itemsChunk);
            $this->line("$count records inserted");
        }
    }
}
