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

        $model = new $modelClass();

        $q = $modelClass::select(
            DB::raw('DATE(time_from) as day_from'),
            DB::raw('DATE_ADD(ANY_VALUE(DATE(time_from)), INTERVAL 1 DAY) as day_to'),
            ...$model->groupableFields(),
            ...$this->getSumAgregablesSelection($model)
        )
            ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
            ->whereDate('time_from', '<=', $threshold->format('Y-m-d'))
            ->groupBy('day_from', ...$model->groupableFields())
            ->orderBy('day_from');

        $fields = implode(',', array_merge(['time_from', 'time_to'], $model->groupableFields(), $model->aggregatedFields()));
        DB::insert("INSERT INTO {$model->getTable()} ($fields) " . $q->toSql(), $q->getBindings());

        $modelClass::whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
            ->whereDate('time_from', '<=', $threshold->format('Y-m-d'))
            ->delete();
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
