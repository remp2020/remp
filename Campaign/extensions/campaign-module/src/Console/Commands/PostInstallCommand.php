<?php

namespace Remp\CampaignModule\Console\Commands;

use Remp\CampaignModule\Console\Command;

class PostInstallCommand extends Command
{
    protected $signature = 'service:post-install';

    protected $description = 'Executes services needed to be run after the Beam installation/update';

    public function handle()
    {
        return self::SUCCESS;
    }
}
