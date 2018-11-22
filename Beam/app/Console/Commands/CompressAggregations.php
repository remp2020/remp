<?php

namespace App\Console\Commands;

use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Model\Aggregable;
use App\SessionDevice;
use App\SessionReferer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompressAggregations extends Command
{
    const COMMAND = 'pageviews:compress-aggregations';

    const COMPRESSION_THRESHOLD_IN_DAYS = 90;

    protected $signature = self::COMMAND;

    protected $description = 'Compress aggregations older than 90 days to daily aggregates.';

    public function handle()
    {
        $this->line('');
        $this->line('<info>***** Compressing aggregations *****</info>');
        $this->line('');
        $this->line('Compressing data older than <info>' . self::COMPRESSION_THRESHOLD_IN_DAYS . 'days</info>.');

        $threshold = Carbon::today()->subDays(self::COMPRESSION_THRESHOLD_IN_DAYS);

        $this->line('Processing <info>ArticlePageviews</info> table');
        $this->aggregate(ArticlePageviews::class, $threshold);

        $this->line('Processing <info>ArticleTimespents</info> table');
        $this->aggregate(ArticleTimespent::class, $threshold);

        $this->line('Processing <info>SessionReferers</info> table');
        $this->aggregate(SessionReferer::class, $threshold);

        $this->line('Processing <info>SessionDevices</info> table');
        $this->aggregate(SessionDevice::class, $threshold);

        $this->line(' <info>OK!</info>');
    }

    public function aggregate($modelClass, Carbon $threshold)
    {
        if (!in_array(Aggregable::class, class_implements($modelClass), true)) {
            throw new \InvalidArgumentException("'$modelClass' doesn't implement '" . Aggregable::class . "' interface");
        }

        $minDate = $modelClass::select(DB::raw('MIN(time_from) as min_time_from'))
            ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
            ->first();

        if (!$minDate) {
            return;
        }

        $model = new $modelClass();

        $minTimeFrom = Carbon::parse($minDate->min_time_from)->startOfDay();
        $iterator = clone $minTimeFrom;

        while ($iterator->lte($threshold)) {
            $this->line("Compressing data for day " . $iterator->toDateString());
            $dayValues = $modelClass::select(...$model->groupableFields(), ...$this->getSumAgregablesSelection($model))
                ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
                ->whereDate('time_from', $iterator->format('Y-m-d'))
                ->groupBy(...$model->groupableFields())
                ->get();

            foreach ($dayValues as $dayValue) {
                $toInsert = [
                    'time_from' => clone $iterator,
                    'time_to' => (clone $iterator)->addDay(),
                ];

                foreach (array_merge($model->aggregatedFields(), $model->groupableFields()) as $field) {
                    $toInsert[$field] = $dayValue->$field;
                }

                $modelClass::create($toInsert);
            }

            $modelClass::whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
                ->whereDate('time_from', $iterator->format('Y-m-d'))
                ->delete();

            $iterator->addDay();
        }
    }

    private function getSumAgregablesSelection(Aggregable $model): array
    {
        $items = [];
        foreach ($model->aggregatedFields() as $field) {
            $items[] = DB::raw("sum($field) as $field");
        }
        return $items;
    }
}
