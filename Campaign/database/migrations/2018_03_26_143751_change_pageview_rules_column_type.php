<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangePageviewRulesColumnType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pageviewRulesData = DB::table('campaigns')->select('id', 'pageview_rules')->get();

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn("pageview_rules");
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->json("pageview_rules")->nullable(true);
        });

        foreach($pageviewRulesData as $row) {
            DB::table('campaigns')->where('id', $row->id)->update([
                'pageview_rules' => $row->pageview_rules
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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string("pageview_rules")->change();
        });
    }
}
