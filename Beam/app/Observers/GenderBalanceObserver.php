<?php

namespace App\Observers;

use App\Jobs\GenderBalanceJob;
use Remp\BeamModule\Model\Article;

class GenderBalanceObserver
{
    public function saved(Article $article)
    {
        // do nothing if null or same value
        if (is_null($article->image_url) || $article->isClean('image_url')) {
            return;
        }

        GenderBalanceJob::dispatch($article);
    }
}
