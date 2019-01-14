<?php

namespace App\Console\Commands;

use App\Model\ConversionCommerceEvent;
use App\Model\ConversionGeneralEvent;
use App\Model\ConversionPageviewEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteOldAggregations extends Command
{
    const COMMAND = 'data:delete-old-aggregations';

    protected $signature = self::COMMAND . ' {--days=}';

    protected $description = 'Delete old aggregated data.';

    public function handle()
    {
        $sub = (int)($this->option('days') ?? config('beam.aggregated_data_retention_period'));

        if ($sub < 0) {
            $this->info("Negative number of days ($sub), not deleting data.");
            return;
        }

        $this->line('');
        $this->line('<info>***** Deleting old aggregations *****</info>');
        $this->line('');
        $this->line('Deleting aggregated data older than <info>' . $sub . ' days</info>.');

        $threshold = Carbon::today()->subDays($sub);

        $this->deleteConversionEventsData($threshold);

        $this->line(' <info>OK!</info>');
    }


    private function deleteConversionEventsData($threshold)
    {
        $this->line('Deleting <info>Conversions events</info> data');
        ConversionCommerceEvent::where('time', '<', $threshold)->delete();
        ConversionGeneralEvent::where('time', '<', $threshold)->delete();
        ConversionPageviewEvent::where('time', '<', $threshold)->delete();
    }
}
