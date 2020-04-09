<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExternalIdColumnToSectionsTagsAuthorsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('external_id')->after('id')->nullable();
            $table->index(['external_id']);
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->string('external_id')->after('id')->nullable();
            $table->index(['external_id']);
        });
        Schema::table('authors', function (Blueprint $table) {
            $table->string('external_id')->after('id')->nullable();
            $table->index(['external_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    }
}
