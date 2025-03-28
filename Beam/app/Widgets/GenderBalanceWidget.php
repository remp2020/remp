<?php

namespace App\Widgets;

use App\Jobs\GenderBalanceJob;
use App\Model\ArticleMeta;
use Arrilot\Widgets\AbstractWidget;

class GenderBalanceWidget extends AbstractWidget
{
    public function run()
    {
        if (!config('internal.gender_balance_enabled')) {
            return '';
        }

        $article = request()->article;
        if (!isset($article)) {
            throw new \Exception('GenderBalanceWidget used in view without article.');
        }

        $menCountMeta = ArticleMeta::where('article_id', $article->id)
            ->where('key', GenderBalanceJob::MEN_COUNT_KEY)
            ->first();

        $womenCountMeta = ArticleMeta::where('article_id', $article->id)
            ->where('key', GenderBalanceJob::WOMEN_COUNT_KEY)
            ->first();

        if (isset($menCountMeta, $womenCountMeta) && ((int) $womenCountMeta->value + (int) $menCountMeta->value) > 0) {
            $womenPercentage = round(100 * (int) $womenCountMeta->value / ((int) $womenCountMeta->value + (int) $menCountMeta->value), 2);
        }

        return view('widgets.gender_balance', [
            'menCount' => $menCountMeta->value ?? null,
            'womenCount' => $womenCountMeta->value ?? null,
            'womenPercentage' => $womenPercentage ?? null
        ]);
    }
}
