<?php

namespace Remp\CampaignModule;

use Remp\CampaignModule\Console\Commands\AggregateCampaignStats;
use Remp\CampaignModule\Jobs\CacheSegmentJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

class Scheduler
{
    public function schedule(Schedule $schedule)
    {
        // Collect campaign stats if Beam Journal is configured
        $beamJournalConfigured = !empty(config('services.remp.beam.segments_addr'));
        if ($beamJournalConfigured) {
            $schedule->command(AggregateCampaignStats::COMMAND)
                ->everyMinute()
                ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
                ->appendOutputTo(storage_path('logs/aggregate_campaign_stats.log'));
        }

        // invalidate segments cache
        try {
            $campaigns = \Remp\CampaignModule\Campaign::selectRaw('campaigns.*')
                ->join('schedules', 'campaigns.id', '=', 'campaign_id')
                ->where(function (\Illuminate\Database\Eloquent\Builder $query) {
                    $query
                        ->whereNull('end_time')
                        ->orWhere('end_time', '>=', Carbon::now());
                })
                ->whereIn('status', [\Remp\CampaignModule\Schedule::STATUS_READY, \Remp\CampaignModule\Schedule::STATUS_EXECUTED, \Remp\CampaignModule\Schedule::STATUS_PAUSED])->cursor();

            /** @var \Remp\CampaignModule\Campaign $campaign */
            foreach ($campaigns as $campaign) {
                foreach ($campaign->segments as $campaignSegment) {
                    $schedule->job(new CacheSegmentJob($campaignSegment, true))
                        ->hourly()
                        ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
                        ->appendOutputTo(storage_path('logs/cache_segments.log'));

                    $schedule->job(new CacheSegmentJob($campaignSegment, false))
                        ->everyMinute()
                        ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
                        ->appendOutputTo(storage_path('logs/cache_segments.log'));
                }
            }
        } catch (\PDOException $e) {
            // no action, the tables are not ready yet
        }
    }
}
