<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToArticleViewsSnapshots extends Migration
{
    public function up()
    {
        Schema::table('article_views_snapshots', function(Blueprint $table) {
            $table->index(['external_article_id']);
            $table->index(['property_token']);
            $table->index(['time', 'derived_referer_medium', 'explicit_referer_medium']);
        });
    }
}
