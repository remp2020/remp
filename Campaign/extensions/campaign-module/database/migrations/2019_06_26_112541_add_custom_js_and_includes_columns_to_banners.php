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
            $table->text('js')->nullable()->after('close_text');
            $table->json('js_includes')->nullable()->after('js');
            $table->json('css_includes')->nullable()->after('js_includes');
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
            $table->dropColumn(['js', 'js_includes', 'css_includes']);
        });
    }
}
