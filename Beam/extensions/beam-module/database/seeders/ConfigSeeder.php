<?php

namespace Remp\BeamModule\Database\Seeders;

use Illuminate\Database\Seeder;
use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigCategory;
use Remp\BeamModule\Model\Config\ConfigNames;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dashboardConfigCategory = ConfigCategory::firstOrCreate([
            'code' => ConfigCategory::CODE_DASHBOARD,
            'display_name' => 'Dashboard'
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::DASHBOARD_FRONTPAGE_REFERER,
            'config_category_id' => $dashboardConfigCategory->id,
            'display_name' => 'Dashboard front-page referer',
            'description' => 'For filtering traffic coming from a front page, please specify a referrer (e.g. https://dennikn.sk/, with trailing slash)',
            'type' => 'string',
            'value' => null, // by default, nothing specified
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_MULTIPLIER,
            'config_category_id' => $dashboardConfigCategory->id,
            'display_name' => 'Conversion rate multiplier',
            'description' => 'Conversion rate multiplier',
            'type' => 'integer',
            'value' => 1
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_DECIMAL_NUMBERS,
            'config_category_id' => $dashboardConfigCategory->id,
            'display_name' => 'Conversion rate decimal numbers',
            'description' => 'Number of decimal numbers shown when displaying conversion rate',
            'type' => 'integer',
            'value' => 5
        ]);

        $authorSegmentsConfigCategory = ConfigCategory::firstOrCreate([
            'code' => ConfigCategory::CODE_SECTION_SEGMENTS,
            'display_name' => 'Section Segments'
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_MIN_RATIO,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Author segments criterion - minimal ratio',
            'description' => 'Minimal ration of user/all articles read by user',
            'type' => 'float',
            'value' => 0.25 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_MIN_AVERAGE_TIMESPENT,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Author segments criterion - minimal avegate timespent',
            'description' => 'Minimal average time spent on author articles',
            'type' => 'int',
            'value' => 120 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_MIN_VIEWS,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Author segments criterion - minimal number of pageviews',
            'description' => 'Minimal number of page views of author articles',
            'type' => 'int',
            'value' => 5 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST,
            'config_category_id' => $authorSegmentsConfigCategory->id,
            'display_name' => 'Author segments days threshold',
            'description' => 'Compute author segments from data not older than given number of days',
            'type' => 'int',
            'value' => 30
        ]);

        $sectionSegmentsConfigCategory = ConfigCategory::firstOrCreate([
            'code' => ConfigCategory::CODE_SECTION_SEGMENTS,
            'display_name' => 'Section Segments'
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_MIN_RATIO,
            'config_category_id' => $sectionSegmentsConfigCategory->id,
            'display_name' => 'Section segments criterion - minimal ratio',
            'description' => 'Minimal ration of user/all articles read by user',
            'type' => 'float',
            'value' => 0.25 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_MIN_AVERAGE_TIMESPENT,
            'config_category_id' => $sectionSegmentsConfigCategory->id,
            'display_name' => 'Section segments criterion - minimal avegate timespent',
            'description' => 'Minimal average time spent on author articles',
            'type' => 'int',
            'value' => 120 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_MIN_VIEWS,
            'config_category_id' => $sectionSegmentsConfigCategory->id,
            'display_name' => 'Section segments criterion - minimal number of pageviews',
            'description' => 'Minimal number of page views of author articles',
            'type' => 'int',
            'value' => 5 // empirical value
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::SECTION_SEGMENTS_DAYS_IN_PAST,
            'config_category_id' => $sectionSegmentsConfigCategory->id,
            'display_name' => 'Section segments days threshold',
            'description' => 'Compute author segments from data not older than given number of days (allowed values: 30, 60, 90)',
            'type' => 'int',
            'value' => 30
        ]);
    }
}
