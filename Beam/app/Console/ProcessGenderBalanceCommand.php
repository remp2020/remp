<?php

namespace App\Console;

use App\GenderBalance;
use App\Jobs\GenderBalanceJob;
use App\Model\ArticleMeta;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Remp\BeamModule\Model\Article;

class ProcessGenderBalanceCommand extends Command
{
    protected $signature = 'dennikn:gender-balance
        {--from= : Articles published after the date}
        {--to= : Articles published before the date}';

    protected $description = 'Create a new migration file';

    public function handle(GenderBalance $genderBalance): void
    {
        $articlesQuery = Article::query()->whereNotNull('image_url');

        if ($fromOption = $this->option('from')) {
            $from = new Carbon($fromOption);
            $articlesQuery->whereDate('published_at', '>=', $from);
        }
        if ($toOption = $this->option('to')) {
            $to = new Carbon($toOption);
            $articlesQuery->whereDate('published_at', '<', $to);
        }

        $articles = $articlesQuery->cursor();

        $this->output->writeln('Starting gender balance processing:');

        /** @var Article $article */
        foreach ($articles as $article) {
            $this->output->write("  * {$article->url}: ");

            try {
                GenderBalanceJob::dispatchSync($article);
            } catch (\Exception $e) {
                $this->output->writeln("ERROR ({$e->getMessage()}");
            }

            $menCountMeta = ArticleMeta::where('article_id', $article->id)
                ->where('key', GenderBalanceJob::MEN_COUNT_KEY)
                ->first()
                ?->value;

            $womenCountMeta = ArticleMeta::where('article_id', $article->id)
                ->where('key', GenderBalanceJob::WOMEN_COUNT_KEY)
                ->first()
                ?->value;

            $this->output->writeln("OK ({$menCountMeta}M, {$womenCountMeta}F)");
        }

        $this->output->writeln('DONE!');
    }
}
