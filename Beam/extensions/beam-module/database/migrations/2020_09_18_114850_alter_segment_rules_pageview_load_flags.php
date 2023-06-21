<?php

use Remp\BeamModule\Model\SegmentRule;
use Illuminate\Database\Migrations\Migration;

class AlterSegmentRulesPageviewLoadFlags extends Migration
{
    /**
     * Changing pageview/load flag '_article' to 'is_article'
     *
     * @return void
     */
    public function up()
    {

        foreach (SegmentRule::all() as $segmentRule) {
            $save = false;
            $flags = $segmentRule->flags;

            foreach ($flags as $k => $f) {
                if ($f['key'] === '_article') {
                    $flags[$k]['key'] = 'is_article';
                    $save = true;
                }
            }
            if ($save) {
                $segmentRule->flags = $flags;
                $segmentRule->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (SegmentRule::all() as $segmentRule) {
            $save = false;
            $flags = $segmentRule->flags;

            foreach ($flags as $k => $f) {
                if ($f['key'] === 'is_article') {
                    $flags[$k]['key'] = '_article';
                    $save = true;
                }
            }
            if ($save) {
                $segmentRule->flags = $flags;
                $segmentRule->save();
            }
        }
    }
}
