<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorRefererRelatedColumnsToSourceInCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->renameColumn('referer_filter', 'source_filter');
            $table->renameColumn('referer_patterns', 'source_patterns');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->renameColumn('source_filter', 'referer_filter');
            $table->renameColumn('source_patterns', 'referer_patterns');
        });
    }
}
