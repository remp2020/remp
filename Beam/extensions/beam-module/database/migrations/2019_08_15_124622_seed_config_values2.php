<?php

use Remp\BeamModule\Model\Config\ConfigNames;
use Remp\BeamModule\Model\Config\Config;
use Illuminate\Database\Migrations\Migration;

class SeedConfigValues2 extends Migration
{
    public function up()
    {
        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_MULTIPLIER,
            'display_name' => 'Conversion rate multiplier',
            'description' => 'Conversion rate multiplier',
            'type' => 'integer',
            'value' => 1
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_DECIMAL_NUMBERS,
            'display_name' => 'Conversion rate decimal numbers',
            'description' => 'Number of decimal numbers shown when displaying conversion rate',
            'type' => 'integer',
            'value' => 5
        ]);
    }

    public function down()
    {
        Config::where('name', ConfigNames::CONVERSION_RATE_MULTIPLIER)->delete();
        Config::where('name', ConfigNames::CONVERSION_RATE_DECIMAL_NUMBERS)->delete();
    }
}
