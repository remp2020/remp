<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ArticleViewsSnapshotsAddIndexTimePropertyToken extends Migration
{
    public function up(): void
    {
        Schema::table('article_views_snapshots', function(Blueprint $table) {
            $table->index(['time', 'property_token'], 'article_views_snapshots_time_property_token_index');
        });
    }
}
