<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Campaign;

class CampaignsRefreshCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:refresh-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes cached campaigns and cache the latest version immediatelly.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $activeCampaignIds = Campaign::refreshActiveCampaignsCache();

        foreach (Campaign::whereIn('id', $activeCampaignIds)->get() as $campaign) {
            $this->line(sprintf('Refreshing campaign: <info>%s</info>', $campaign->name));
            $campaign->cache();
        };

        $this->line('Campaigns cache refreshed.');
        return true;
    }
}
