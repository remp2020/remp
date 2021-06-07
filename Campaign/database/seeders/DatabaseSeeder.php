<?php

namespace Database\Seeders;

use Database\Seeders\CountrySeeder;
use Database\Seeders\CampaignSeeder;
use Illuminate\Database\Seeder;

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
