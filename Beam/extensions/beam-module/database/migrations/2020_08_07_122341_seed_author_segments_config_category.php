<?php

use Remp\BeamModule\Model\Config\ConfigCategory;
use Remp\BeamModule\Model\Config\ConfigNames;
use Remp\BeamModule\Model\Config\Config;
use Illuminate\Database\Migrations\Migration;

class SeedAuthorSegmentsConfigCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $authorSegmentsConfigCategory = ConfigCategory::firstOrCreate([
            'code' => ConfigCategory::CODE_AUTHOR_SEGMENTS,
            'display_name' => 'Author Segments'
        ]);

        $this->updateAuthorConfigsCategory($authorSegmentsConfigCategory);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $dashboardConfigCategory = ConfigCategory::firstOrCreate([
            'code' => ConfigCategory::CODE_DASHBOARD,
            'display_name' => 'Dashboard'
        ]);

        $this->updateAuthorConfigsCategory($dashboardConfigCategory);

        $authorSegmentsConfigCategory = ConfigCategory::where('code', ConfigCategory::CODE_AUTHOR_SEGMENTS)->first();
        $authorSegmentsConfigCategory->delete();
    }

    private function updateAuthorConfigsCategory(ConfigCategory $configCategory)
    {
        $authorConfigNames = [
            ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST,
            ConfigNames::AUTHOR_SEGMENTS_MIN_VIEWS,
            ConfigNames::AUTHOR_SEGMENTS_MIN_AVERAGE_TIMESPENT,
            ConfigNames::AUTHOR_SEGMENTS_MIN_RATIO
        ];

        foreach ($authorConfigNames as $authorConfigName) {
            $config = Config::where('name', $authorConfigName)->first();
            $config->configCategory()->associate($configCategory);
            $config->save();
        }
    }
}
