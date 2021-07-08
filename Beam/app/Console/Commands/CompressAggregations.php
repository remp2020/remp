<?php

namespace App\Console\Commands;

use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Model\Aggregable;
use App\SessionDevice;
use App\SessionReferer;
use Carbon\Carbon;
use App\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CompressAggregations extends Command
{
    const COMMAND = 'pageviews:compress-aggregations';

    protected $signature = self::COMMAND . ' {--threshold=} {--debug}';

    protected $description = 'Compress aggregations older than 90 days to daily aggregates.';

    public function handle()
    {
        $debug = $this->option('debug') ?? false;
        $sub = (int)($this->option('threshold') ?? config('beam.aggregated_data_retention_period'));

        if ($sub < 0) {
            $this->info("Negative threshold given ($sub), not compressing data.");
            return 0;
        }

        $this->line('');
        $this->line('<info>***** Compressing aggregations *****</info>');
        $this->line('');
        $this->line('Compressing data older than <info>' . $sub . ' days</info>.');

        $threshold = Carbon::today()->subDays($sub);

        $this->line('Processing <info>ArticlePageviews</info> table');
        $this->aggregate(ArticlePageviews::class, $threshold, $debug);

        $this->line('Processing <info>ArticleTimespents</info> table');
        $this->aggregate(ArticleTimespent::class, $threshold, $debug);

        $this->line('Processing <info>SessionReferers</info> table');
        $this->aggregate(SessionReferer::class, $threshold, $debug);

        $this->line('Processing <info>SessionDevices</info> table');
        $this->aggregate(SessionDevice::class, $threshold, $debug);

        $this->line(' <info>OK!</info>');
        return 0;
    }

    public function aggregate($modelClass, Carbon $threshold, bool $debug)
    {
        if (!in_array(Aggregable::class, class_implements($modelClass), true)) {
            throw new \InvalidArgumentException("'$modelClass' doesn't implement '" . Aggregable::class . "' interface");
        }

        /** @var Model|Aggregable $model */
        $model = new $modelClass();
        $rows = $model::select(
            array_merge(
                [
                    DB::raw('DATE(time_from) as day_from'),
                    DB::raw('DATE_ADD(ANY_VALUE(DATE(time_from)), INTERVAL 1 DAY) as day_to'),
                    DB::raw("GROUP_CONCAT(DISTINCT id ORDER BY id SEPARATOR',') as ids_to_delete"),
                ],
                $model->groupableFields(),
                $this->getSumAgregablesSelection($model)
            )
        )
            ->whereIn('id', function ($query) use ($threshold) {
                $query->select('id')
                    ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
                    ->whereDate('time_from', '<=', $threshold->format('Y-m-d'));
            })
            ->groupBy('day_from', ...$model->groupableFields())
            ->orderBy('day_from')
            ->cursor();

        $limit = 1000;
        $i = 0;
        $compressedRecords = [];
        $idsToDelete = [];

        foreach ($rows as $row) {
            $data = $row->toArray();
            $data['time_from'] = $data['day_from'];
            $data['time_to'] = $data['day_to'];

            array_push($idsToDelete, ...explode(',', $data['ids_to_delete']));
            unset($data['day_from'], $data['day_to'], $data['ids_to_delete']);

            $compressedRecords[] = $data;

            if ($i >= $limit) {
                if ($debug) {
                    $this->getOutput()->write('.');
                }

                DB::transaction(function () use ($modelClass, $compressedRecords, $idsToDelete) {
                    $modelClass::insert($compressedRecords);
                    $modelClass::destroy($idsToDelete);
                });

                $i = 0;
                $compressedRecords = [];
                $idsToDelete = [];
            }
            $i += 1;
        }

        if (count($compressedRecords)) {
            DB::transaction(function () use ($modelClass, $compressedRecords, $idsToDelete) {
                $modelClass::insert($compressedRecords);
                $modelClass::destroy($idsToDelete);
            });
        }

        if ($debug) {
            $this->line('');
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
