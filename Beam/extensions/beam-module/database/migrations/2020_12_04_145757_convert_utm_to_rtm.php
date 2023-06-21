<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertUtmToRtm extends Migration
{
    public function up()
    {
        Schema::table('conversion_commerce_events', function (Blueprint $table) {
            $table->renameColumn('utm_campaign', 'rtm_campaign');
            $table->renameColumn('utm_content', 'rtm_content');
            $table->renameColumn('utm_medium', 'rtm_medium');
            $table->renameColumn('utm_source', 'rtm_source');
        });

        Schema::table('conversion_general_events', function (Blueprint $table) {
            $table->renameColumn('utm_campaign', 'rtm_campaign');
            $table->renameColumn('utm_content', 'rtm_content');
            $table->renameColumn('utm_medium', 'rtm_medium');
            $table->renameColumn('utm_source', 'rtm_source');
        });

        Schema::table('conversion_pageview_events', function (Blueprint $table) {
            $table->renameColumn('utm_campaign', 'rtm_campaign');
            $table->renameColumn('utm_content', 'rtm_content');
            $table->renameColumn('utm_medium', 'rtm_medium');
            $table->renameColumn('utm_source', 'rtm_source');
        });
    }

    public function down()
    {
        Schema::table('conversion_commerce_events', function (Blueprint $table) {
            $table->renameColumn('rtm_campaign', 'utm_campaign');
            $table->renameColumn('rtm_content', 'utm_content');
            $table->renameColumn('rtm_medium', 'utm_medium');
            $table->renameColumn('rtm_source', 'utm_source');
        });

        Schema::table('conversion_general_events', function (Blueprint $table) {
            $table->renameColumn('rtm_campaign', 'utm_campaign');
            $table->renameColumn('rtm_content', 'utm_content');
            $table->renameColumn('rtm_medium', 'utm_medium');
            $table->renameColumn('rtm_source', 'utm_source');
        });

        Schema::table('conversion_pageview_events', function (Blueprint $table) {
            $table->renameColumn('rtm_campaign', 'utm_campaign');
            $table->renameColumn('rtm_content', 'utm_content');
            $table->renameColumn('rtm_medium', 'utm_medium');
            $table->renameColumn('rtm_source', 'utm_source');
        });
    }
}
