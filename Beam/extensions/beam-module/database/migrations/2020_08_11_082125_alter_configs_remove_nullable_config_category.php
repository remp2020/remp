<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterConfigsRemoveNullableConfigCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('configs');

        if (array_key_exists('configs_config_category_id_foreign', $indexes)) {
            Schema::table('configs', function (Blueprint $table) {
                $table->dropForeign('configs_config_category_id_foreign');
                $table->dropIndex('configs_config_category_id_foreign');
            });
        }

        Schema::table('configs', function (Blueprint $table) {
            $table->integer('config_category_id')
                ->nullable(false)
                ->unsigned()
                ->change();

            $table->foreign('config_category_id')->references('id')->on('config_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indices = $sm->listTableIndexes('configs');

        if (array_key_exists('configs_config_category_id_foreign', $indices)) {
            Schema::table('configs', function (Blueprint $table) {
                $table->dropForeign('configs_config_category_id_foreign');
                $table->dropIndex('configs_config_category_id_foreign');
            });
        }

        Schema::table('configs', function (Blueprint $table) {
            $table->integer('config_category_id')
                ->nullable(true)
                ->unsigned()
                ->change();

            $table->foreign('config_category_id')->references('id')->on('config_categories');
        });
    }
}
