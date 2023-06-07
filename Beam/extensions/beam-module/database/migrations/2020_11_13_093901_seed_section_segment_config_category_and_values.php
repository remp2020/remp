<?php

use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigCategory;
use Remp\BeamModule\Model\Config\ConfigNames;
use Illuminate\Database\Migrations\Migration;

class SeedSectionSegmentConfigCategoryAndValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $authorSegmentsConfigCategory = ConfigCategory::firstOrCreate([
            'code' => ConfigCategory::CODE_SECTION_SEGMENTS,
            'display_name' => 'Section Segments'
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_MIN_RATIO,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Section segments criterion - minimal ratio',
            'description' => 'Minimal ration of user/all articles read by user',
            'type' => 'float',
            'value' => 0.25 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_MIN_AVERAGE_TIMESPENT,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Section segments criterion - minimal avegate timespent',
            'description' => 'Minimal average time spent on author articles',
            'type' => 'int',
            'value' => 120 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_MIN_VIEWS,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Section segments criterion - minimal number of pageviews',
            'description' => 'Minimal number of page views of author articles',
            'type' => 'int',
            'value' => 5 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_DAYS_IN_PAST,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Section segments days threshold',
            'description' => 'Compute author segments from data not older than given number of days (allowed values: 30, 60, 90)',
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
        Config::where('name', ConfigNames::SECTION_SEGMENTS_MIN_RATIO)->delete();
        Config::where('name', ConfigNames::SECTION_SEGMENTS_MIN_VIEWS)->delete();
        Config::where('name', ConfigNames::SECTION_SEGMENTS_MIN_AVERAGE_TIMESPENT)->delete();
        Config::where('name', ConfigNames::SECTION_SEGMENTS_DAYS_IN_PAST)->delete();

        ConfigCategory::where('code', ConfigCategory::CODE_SECTION_SEGMENTS)->delete();
    }
}
