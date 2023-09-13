<?php

namespace App\Jobs;

use App\GenderBalance;
use App\Model\ArticleMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Remp\BeamModule\Model\Article;

class GenderBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MEN_COUNT_KEY = 'article_image_men_count';
    public const WOMEN_COUNT_KEY = 'article_image_women_count';

    private Article $article;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GenderBalance $genderBalance)
    {
        $result = $genderBalance->getGenderCounts($this->article->image_url);

        if (isset($result['men'])) {
            ArticleMeta::updateOrCreate(
                ['article_id' => $this->article->id, 'key' => self::MEN_COUNT_KEY],
                ['value' => $result['men']]
            );
        }

        if (isset($result['women'])) {
            ArticleMeta::updateOrCreate(
                ['article_id' => $this->article->id, 'key' => self::WOMEN_COUNT_KEY],
                ['value' => $result['women']]
            );
        }
    }
}
