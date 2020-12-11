<?php

namespace App\Console\Commands;

use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Model\Aggregable;
use App\SessionDevice;
use App\SessionReferer;
use Carbon\Carbon;
use Eloquent;
use App\Console\Command;
use Illuminate\Support\Facades\DB;

class CompressAggregations extends Command
{
    const COMMAND = 'pageviews:compress-aggregations';

    protected $signature = self::COMMAND . ' {--threshold=}';

    protected $description = 'Compress aggregations older than 90 days to daily aggregates.';

    public function handle()
    {
        $sub = (int)($this->option('threshold') ?? config('beam.aggregated_data_retention_period'));

        if ($sub < 0) {
            $this->info("Negative threshold given ($sub), not compressing data.");
            return;
        }

        $this->line('');
        $this->line('<info>***** Compressing aggregations *****</info>');
        $this->line('');
        $this->line('Compressing data older than <info>' . $sub . ' days</info>.');

        $threshold = Carbon::today()->subDays($sub);

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

        DB::transaction(function () use ($modelClass, $threshold) {
            /** @var Eloquent|Aggregable $model */
            $model = new $modelClass();

            $q = $model::select(
                array_merge(
                    [
                        DB::raw('DATE(time_from) as day_from'),
                        DB::raw('DATE_ADD(ANY_VALUE(DATE(time_from)), INTERVAL 1 DAY) as day_to'),
                    ],
                    $model->groupableFields(),
                    $this->getSumAgregablesSelection($model)
                )
            )
                ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
                ->whereDate('time_from', '<=', $threshold->format('Y-m-d'))
                ->groupBy('day_from', ...$model->groupableFields())
                ->orderBy('day_from');

            $fields = implode(',', array_merge(['time_from', 'time_to'], $model->groupableFields(), $model->aggregatedFields()));
            DB::insert("INSERT INTO {$model->getTable()} ($fields) " . $q->toSql(), $q->getBindings());

            $model::whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
                ->whereDate('time_from', '<=', $threshold->format('Y-m-d'))
                ->delete();
        });
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
