<?php

use Remp\CampaignModule\IdentificationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPublicId extends Migration
{
    use IdentificationTrait;

    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->char('public_id', 6)->after('uuid');
        });
        foreach (DB::query()->from('campaigns')->get() as $item) {
            DB::table('campaigns')
                ->where(['id' => $item->id])
                ->update(['public_id' => self::generatePublicId()]);
        }

        Schema::table('banners', function (Blueprint $table) {
            $table->char('public_id', 6)->after('uuid');
        });
        foreach (DB::query()->from('banners')->get() as $item) {
            DB::table('banners')
                ->where(['id' => $item->id])
                ->update(['public_id' => self::generatePublicId()]);
        }

        Schema::table('campaign_banners', function (Blueprint $table) {
            $table->char('public_id', 6)->after('uuid');
        });
        foreach (DB::query()->from('campaign_banners')->get() as $item) {
            DB::table('campaign_banners')
                ->where(['id' => $item->id])
                ->update(['public_id' => self::generatePublicId()]);
        }
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });

        Schema::table('campaign_banners', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
}
