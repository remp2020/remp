<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // properties

        $hasRegularIndex = DB::select("SHOW INDEX FROM `properties` WHERE Key_name = 'properties_uuid_index'");
        if ($hasRegularIndex) {
            Schema::table('articles', function (Blueprint $blueprint) {
                $blueprint->dropForeign('articles_property_uuid_foreign');
            });
            Schema::table('properties', function (Blueprint $blueprint) {
                $blueprint->dropIndex('properties_uuid_index');
            });
        }
        $hasUniqueIndex = DB::select("SHOW INDEX FROM `properties` WHERE Key_name = 'properties_uuid_unique'");
        if (!$hasUniqueIndex) {
            Schema::table('properties', function (Blueprint $blueprint) {
                $blueprint->unique('uuid');
            });
        }
        Schema::table('articles', function (Blueprint $blueprint) {
            $blueprint->foreign('property_uuid')->references('uuid')->on('properties');
        });

        // accounts

        $hasRegularIndex = DB::select("SHOW INDEX FROM `accounts` WHERE Key_name = 'accounts_uuid_index'");
        if ($hasRegularIndex) {
            Schema::table('accounts', function (Blueprint $blueprint) {
                $blueprint->dropIndex('accounts_uuid_index');
            });
        }
        $hasUniqueIndex = DB::select("SHOW INDEX FROM `accounts` WHERE Key_name = 'accounts_uuid_unique'");
        if (!$hasUniqueIndex) {
            Schema::table('accounts', function (Blueprint $blueprint) {
                $blueprint->unique('uuid');
            });
        }
    }

    public function down()
    {
        // Not reversible: downgrading back to a non-unique index would reintroduce
        // the MySQL 8.4 foreign key constraint incompatibility.
    }
};
