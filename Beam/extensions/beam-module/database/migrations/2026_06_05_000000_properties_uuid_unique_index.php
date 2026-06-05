<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        foreach (['properties', 'accounts'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $hasRegularIndex = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$table}_uuid_index'");
                if ($hasRegularIndex) {
                    $blueprint->dropIndex(['uuid']);
                }

                $hasUniqueIndex = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$table}_uuid_unique'");
                if (!$hasUniqueIndex) {
                    $blueprint->unique(['uuid']);
                }
            });
        }
    }

    public function down()
    {
        // Not reversible: downgrading back to a non-unique index would reintroduce
        // the MySQL 8.4 foreign key constraint incompatibility.
    }
};
