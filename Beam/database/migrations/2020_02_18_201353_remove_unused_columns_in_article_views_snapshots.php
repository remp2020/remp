<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUnusedColumnsInArticleViewsSnapshots extends Migration
{
    public function up()
    {
        Schema::table('article_views_snapshots', function (Blueprint $table) {
            $table->dropIndex('time_referer_mediums');
            $table->dropColumn('count_by_referer');
            $table->dropColumn('explicit_referer_medium');
            $table->renameColumn('derived_referer_medium', 'referer_medium');
            $table->index(['time', 'referer_medium']);
        });
    }
}
