<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\ArticlePageviews;
use Remp\BeamModule\Model\ArticleTimespent;
use Remp\BeamModule\Model\Aggregable;
use Carbon\Carbon;
use Remp\BeamModule\Console\Command;
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

        $this->getOutput()->write('Determining earliest date to compress: ');
        $dateFromRaw = $modelClass::whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')->min('time_from');
        if (!$dateFromRaw) {
            $this->line('nothing to compress!');
            return;
        }
        $dateFrom = (new Carbon($dateFromRaw))->setTime(0, 0, 0);
        $this->line($dateFrom->format('Y-m-d'));

        while ($dateFrom <= $threshold) {
            $this->getOutput()->write("  * Processing {$dateFrom->format('Y-m-d')}: ");

            $dateTo = (clone $dateFrom)->addDay();
            $ids = DB::table($model->getTable())
                ->select('id')
                ->whereRaw('TIMESTAMPDIFF(HOUR, time_from, time_to) < 24')
                ->where('time_from', '>=', $dateFrom)
                ->where('time_from', '<', $dateTo);

            $rows = $model::select(
                array_merge(
                    [
                        DB::raw("GROUP_CONCAT(DISTINCT id ORDER BY id SEPARATOR',') as ids_to_delete"),
                    ],
                    $model->groupableFields(),
                    $this->getSumAgregablesSelection($model)
                )
            )
                ->whereIn('id', $ids)
                ->groupBy(...$model->groupableFields())
                ->cursor();

            $limit = 256;
            $i = 0;
            $compressedRecords = [];
            $idsToDelete = [];

            foreach ($rows as $row) {
                $data = $row->toArray();
                $data['time_from'] = $dateFrom;
                $data['time_to'] = $dateTo;

                foreach (explode(',', $data['ids_to_delete']) as $id) {
                    $idsToDelete[] = (int) $id;
                }
                unset($data['ids_to_delete']);

                $compressedRecords[] = $data;

                if ($i >= $limit) {
                    if ($debug) {
                        $this->getOutput()->write('.');
                    }

                    DB::transaction(function () use ($modelClass, $compressedRecords, $idsToDelete) {
                        $modelClass::insert($compressedRecords);
                        $modelClass::whereIn('id', $idsToDelete)->delete();
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
                    $modelClass::whereIn('id', $idsToDelete)->delete();
                });
            }

            $this->line('OK!');

            $dateFrom = $dateFrom->addDay();
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
