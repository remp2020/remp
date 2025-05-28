<?php

namespace Remp\BeamModule\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Remp\BeamModule\Console\Command;

class PostInstallCommand extends Command
{
    protected $signature = 'service:post-install';

    protected $description = 'Executes services needed to be run after the Beam installation/update';

    public function handle()
    {
        try {
            Artisan::call('db:seed', [
                '--class' => \Remp\BeamModule\Database\Seeders\ConfigSeeder::class,
                '--force' => true,
            ]);

            Artisan::call('db:seed', [
                '--class' => \Remp\BeamModule\Database\Seeders\SegmentGroupSeeder::class,
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            // Do nothing. DB might not be initialized yet, or we're in the CI where there's just no DB.
        }


        return self::SUCCESS;
    }
}
