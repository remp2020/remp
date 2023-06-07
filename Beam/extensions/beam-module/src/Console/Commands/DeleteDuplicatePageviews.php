<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\ArticlePageviews;
use Remp\BeamModule\Console\Command;
use Illuminate\Support\Carbon;

class DeleteDuplicatePageviews extends Command
{
    const COMMAND = 'data:delete-duplicate-pageviews';

    protected $signature = self::COMMAND . ' {--date=}';

    protected $description = 'Delete duplicate pageviews and keeps the shortest available interval.';

    public function handle()
    {
        $dateFrom = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now()->startOfDay();
        $dateTo = (clone $dateFrom)->endOfDay();

        $this->line('');
        $this->line(sprintf("***** Deleting duplicate pageviews starting between <info>%s</info> - <info>%s</info> *****", $dateFrom, $dateTo));
        $this->line('');

        $removeIds = \DB::select("
                     SELECT DISTINCT ap_old.id
                     FROM article_pageviews ap_new
                              JOIN article_pageviews ap_old ON
                                 ap_new.article_id = ap_old.article_id
                             AND ap_old.time_from <= ap_new.time_from
                             AND ap_old.time_to >= ap_new.time_to
                             AND ap_old.id != ap_new.id
                     WHERE ap_new.time_from >= ? AND ap_new.time_from <= ?", [$dateFrom, $dateTo]);
        $removeIds = array_map(static function ($value) {
            return $value->id;
        }, $removeIds);

        $deletedCount = 0;
        while ($ids = array_splice($removeIds, 0, 1000)) {
            $deletedCount += ArticlePageviews::whereIn('id', $ids)->delete();
        }

        $this->line(sprintf("Removed <info>%s</info> rows.", $deletedCount));
        $this->line(' <info>OK!</info>');

        return self::SUCCESS;
    }
}
