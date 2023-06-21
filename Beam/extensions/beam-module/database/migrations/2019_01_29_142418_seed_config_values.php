<?php

use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigNames;
use Illuminate\Database\Migrations\Migration;

class SeedConfigValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_MIN_RATIO,
            'display_name' => 'Author segments criterion - minimal ratio',
            'description' => 'Minimal ration of user/all articles read by user',
            'type' => 'float',
            'value' => 0.25 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_MIN_AVERAGE_TIMESPENT,
            'display_name' => 'Author segments criterion - minimal avegate timespent',
            'description' => 'Minimal average time spent on author articles',
            'type' => 'int',
            'value' => 120 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_MIN_VIEWS,
            'display_name' => 'Author segments criterion - minimal number of pageviews',
            'description' => 'Minimal number of page views of author articles',
            'type' => 'int',
            'value' => 5 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST,
            'display_name' => 'Author segments days threshold',
            'description' => 'Compute author segments from data not older than given number of days',
            'type' => 'int',
            'value' => 30
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Config::where('name', ConfigNames::AUTHOR_SEGMENTS_MIN_RATIO)->delete();
        Config::where('name', ConfigNames::AUTHOR_SEGMENTS_MIN_VIEWS)->delete();
        Config::where('name', ConfigNames::AUTHOR_SEGMENTS_MIN_AVERAGE_TIMESPENT)->delete();
        Config::where('name', ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST)->delete();
    }
}
