<?php

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VariantsUuid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_banners', function (Blueprint $table) {
            $table->uuid('uuid');
        });

        $variants = DB::table('campaign_banners')->get();
        foreach ($variants as $variant) {
            DB::table('campaign_banners')->where('id', $variant->id)->update([
                'uuid' => Uuid::uuid4()->toString()
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
        Schema::table('campaign_banners', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
}
