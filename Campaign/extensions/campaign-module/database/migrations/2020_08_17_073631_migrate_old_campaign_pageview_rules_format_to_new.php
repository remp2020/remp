<?php

use Illuminate\Database\Migrations\Migration;

class MigrateOldCampaignPageviewRulesFormatToNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $campaigns = \DB::table('campaigns')->select(['id', 'pageview_rules'])->get();

        foreach ($campaigns as $campaign) {
            if ($campaign->pageview_rules === null) {
                continue;
            }

            $stopCampaign = false;
            $newPageviewRules = [
                'display_banner' => 'always',
                'display_banner_every' => 2,
                'display_times' => false,
                'display_n_times' => 2,
            ];
            $pageviewRules = json_decode($campaign->pageview_rules, true);
            foreach ($pageviewRules as $pageviewRule) {
                if (in_array($pageviewRule['rule'], ['since', 'before'])) {
                    $stopCampaign = true;
                    continue;
                }

                if ($pageviewRule['rule'] === 'every' && $pageviewRule['num'] !== null && $pageviewRule['num'] != 1) {
                    $newPageviewRules['display_banner'] = 'every';
                    $newPageviewRules['display_banner_every'] = $pageviewRule['num'];
                }
            }

            $campaignObj = \Remp\CampaignModule\Campaign::find($campaign->id);
            $campaignObj->pageview_rules = $newPageviewRules;
            $campaignObj->save();

            if ($stopCampaign) {
                $stopped = false;
                /** @var \Remp\CampaignModule\Schedule $schedule */
                foreach ($campaignObj->schedules()->runningOrPlanned()->get() as $schedule) {
                    $schedule->status = \Remp\CampaignModule\Schedule::STATUS_STOPPED;
                    $schedule->end_time = \Carbon\Carbon::now();
                    $schedule->save();
                    $stopped = true;
                }
                if ($stopped) {
                    $output->writeln('Campaign with ID: ' . $campaignObj->id . ' was stopped because we cant translate its pageview rules to new format. Old pageview rules were: ' . $campaign->pageview_rules);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
