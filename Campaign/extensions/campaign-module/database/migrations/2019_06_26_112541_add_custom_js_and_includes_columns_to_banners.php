<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomJsAndIncludesColumnsToBanners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->text('js')->nullable(true);
            $table->json('js_includes')->nullable(true);
            $table->json('css_includes')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('js');
            $table->dropColumn('js_includes')->nullable(true);
            $table->dropColumn('css_includes')->nullable(true);
        });
    }
}
