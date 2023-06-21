<?php

use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigNames;
use Illuminate\Database\Migrations\Migration;

class ChangeAuthorsSegmentDaysThresholdDescription extends Migration
{
    public function up()
    {
        Config::where('name', ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST)->update([
            'description' => 'Compute author segments from data not older than given number of days (allowed values: 30, 60, 90)',
        ]);
    }

    public function down()
    {
        Config::where('name', ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST)->update([
            'description' => 'Compute author segments from data not older than given number of days',
        ]);
    }
}
