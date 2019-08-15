<?php

use App\Model\Config\ConfigNames;
use App\Model\Config\Config;
use Illuminate\Database\Migrations\Migration;

class SeedConfigValues2 extends Migration
{
    public function up()
    {
        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_MULTIPLIER,
            'display_name' => 'Conversion rate multiplier',
            'description' => 'Conversion rate multiplier for dashboard',
            'type' => 'integer',
            'value' => 10000 // empirical value
        ]);
    }

    public function down()
    {
        Config::where('name', ConfigNames::CONVERSION_RATE_MULTIPLIER)->delete();
    }
}
