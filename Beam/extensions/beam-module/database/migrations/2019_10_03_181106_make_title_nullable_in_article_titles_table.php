<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeTitleNullableInArticleTitlesTable extends Migration
{
    public function up()
    {
        Schema::table('article_titles', function (Blueprint $table) {
            $table->string('title', 768)
                ->nullable()
                ->change();
        });
    }
}
