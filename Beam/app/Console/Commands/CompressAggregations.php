<?php

namespace App\Console\Commands;

use App\ArticlePageviews;
use App\ArticleTimespent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CompressAggregations extends Command
{
    const COMMAND = 'pageviews:compress-aggregations';

    const COMPRESSION_THRESHOLD_IN_DAYS = 90;

    protected $signature = self::COMMAND;

    protected $description = 'Compress aggregations older than 90 days to daily aggregates.';

    public function handle()
    {
        $threshold = Carbon::today()->subDays(self::COMPRESSION_THRESHOLD_IN_DAYS);
        $this->aggregateTable(ArticlePageviews::class, $threshold);
        $this->aggregateTable(ArticleTimespent::class, $threshold);
    }

    private function aggregateTable($tableClass, $threshold)
    {
        $items = $tableClass::where('time_from', '<=', $threshold)
            ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
            ->get();

        $aggregates = [];
        $idsToDelete = [];
        foreach ($items as $item) {
            $dayIndex = $item->time_from->startOfDay()->timestamp;

            $idsToDelete[] = $item->id;

            $aggregates[$item->article_id] = $aggregates[$item->article_id] ?? [];
            $aggregates[$item->article_id][$dayIndex] = $aggregates[$item->article_id][$dayIndex] ?? [
                    'sum' => 0,
                    'signed_in' => 0,
                    'subscribers' => 0
                ];

            $aggregates[$item->article_id][$dayIndex]['sum'] += $item->sum;
            $aggregates[$item->article_id][$dayIndex]['signed_in'] += $item->signed_in;
            $aggregates[$item->article_id][$dayIndex]['subscribers'] += $item->subscribers;
        }

        foreach ($aggregates as $articleId => $daysData) {
            foreach ($daysData as $dayIndex => $values) {
                [$from, $to] = $this->dayIndexToInterval($dayIndex);

                $tableClass::create([
                    'article_id' => $articleId,
                    'time_from' => $from,
                    'time_to' => $to,
                    'sum' => $values['sum'],
                    'signed_in' => $values['signed_in'],
                    'subscribers' => $values['subscribers'],
                ]);
            }
        }
        if (!empty($idsToDelete)) {
            $tableClass::destroy($idsToDelete);
        }
    }

    private function dayIndexToInterval($dayIndex)
    {
        $from = Carbon::createFromTimestampUTC($dayIndex);
        $to = (clone $from)->addDay();
        return [$from, $to];
    }
}
