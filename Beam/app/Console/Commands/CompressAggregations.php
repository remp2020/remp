<?php

namespace App\Console\Commands;

use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Model\Aggregable;
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
        if (!in_array(Aggregable::class, class_implements($tableClass), true)) {
            throw new \InvalidArgumentException("'$tableClass' doesn't implement '" . Aggregable::class . "' interface");
        }

        $items = $tableClass::where('time_from', '<=', $threshold)
            ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
            ->get();

        $model = new $tableClass();

        $aggregates = [];
        $idsToDelete = [];
        foreach ($items as $item) {
            $dayIndex = $item->time_from->startOfDay()->timestamp;

            $idsToDelete[] = $item->id;

            $aggregates[$item->article_id] = $aggregates[$item->article_id] ?? [];
            $aggregates[$item->article_id][$dayIndex] = $aggregates[$item->article_id][$dayIndex] ??
                $this->getDefaultAggregables($model);

            foreach ($model->aggregatedFields() as $field) {
                $aggregates[$item->article_id][$dayIndex][$field] += $item->$field;
            }
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

    private function getDefaultAggregables(Aggregable $model)
    {
        $items = [];
        foreach ($model->aggregatedFields() as $field) {
            $items[$field] = 0;
        }
        return $items;
    }

    private function dayIndexToInterval($dayIndex)
    {
        $from = Carbon::createFromTimestampUTC($dayIndex);
        $to = (clone $from)->addDay();
        return [$from, $to];
    }
}
