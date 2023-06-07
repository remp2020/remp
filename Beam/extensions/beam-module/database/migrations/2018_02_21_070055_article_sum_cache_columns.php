<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ArticleSumCacheColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articles', function(Blueprint $table) {
            $table->bigInteger('pageview_sum')->default(0)->after('published_at');
            $table->bigInteger('timespent_sum')->default(0)->after('pageview_sum');
            $table->index(['published_at']);
        });

        $sql = <<<SQL
UPDATE articles

-- sum pageviews per article
LEFT JOIN ( 
  SELECT SUM(sum) as sum, article_id FROM article_pageviews
  GROUP BY article_id
) sub1 ON sub1.article_id = articles.id

-- sum timespent per article
LEFT JOIN ( 
  SELECT SUM(sum) as sum, article_id FROM article_timespents
  GROUP BY article_id
) sub2 ON sub2.article_id = articles.id

SET pageview_sum = COALESCE(sub1.sum, 0), timespent_sum = COALESCE(sub2.sum, 0) 
SQL;
        DB::update($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articles', function(Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropColumn(['pageview_sum', 'timespent_sum']);
        });
    }
}
