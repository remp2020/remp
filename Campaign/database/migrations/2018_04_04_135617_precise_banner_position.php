<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PreciseBannerPosition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->integer('offset_vertical')->nullable()->after('position');
            $table->integer('offset_horizontal')->nullable()->after('position');
        });

        foreach (DB::query()->from('banners')->get() as $banner) {
            $pos = config('banners.positions.' . $banner->position);

            $query = DB::table('banners')->where([
                'id' => $banner->id
            ]);

            $query->update([
                'offset_vertical' => intval(isset($pos['style']['top']) ? $pos['style']['top'] : $pos['style']['bottom']),
                'offset_horizontal' => intval(isset($pos['style']['left']) ? $pos['style']['left'] : $pos['style']['right']),
            ]);
        }

        Schema::table('banners', function (Blueprint $table) {
            $table->integer('offset_vertical')->nullable(false)->change();
            $table->integer('offset_horizontal')->nullable(false)->change();
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
            $table->dropColumn('offset_horizontal');
            $table->dropColumn('offset_vertical');
        });
    }
}
