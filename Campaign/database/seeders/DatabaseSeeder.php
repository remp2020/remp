<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Remp\CampaignModule\Database\Seeders\CampaignSeeder;
use Remp\CampaignModule\Database\Seeders\CountrySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CountrySeeder::class);
        $this->call(CampaignSeeder::class);
    }
}
