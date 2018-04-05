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
            $table->integer('position_top')->default(0);
            $table->integer('position_left')->default(0);
            $table->integer('position_right')->default(0);
            $table->integer('position_bottom')->default(0);
        });

        foreach (DB::query()->from('banners')->get() as $banner) {
            $pos = config('banners.positions.' . $banner->position);

            $query = DB::table('banners')->where([
                'id' => $banner->id
            ]);

            if ($banner->template == 'bar') {
                $pos['style'] = [
                    'top' => 0,
                    'left' => 0,
                    'right' => 0,
                    'bottom' => 0
                ];
            }

            $query->update([
                'position_top' => isset($pos['style']['top']) ? intval($pos['style']['top']) : 0,
                'position_left' => isset($pos['style']['left']) ? intval($pos['style']['left']) : 0,
                'position_right' => isset($pos['style']['right']) ? intval($pos['style']['right']) : 0,
                'position_bottom' => isset($pos['style']['bottom']) ? intval($pos['style']['bottom']) : 0,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('position_top');
            $table->dropColumn('position_left');
            $table->dropColumn('position_right');
            $table->dropColumn('position_bottom');
        });
    }
}
