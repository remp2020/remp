<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\ArticlePageviews;
use Remp\BeamModule\Helpers\DebugProxy;
use Remp\BeamModule\Console\Command;
use Illuminate\Support\Carbon;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;
use Symfony\Component\Console\Helper\ProgressBar;

class AggregatePageviewLoadJob extends Command
{
    const COMMAND = 'pageviews:aggregate-load';

    protected $signature = self::COMMAND . ' {--now=} {--debug} {--article_id=}';

    protected $description = 'Reads pageview/load data from journal and stores aggregated data';

    public function handle(JournalContract $journalContract)
    {
        $debug = $this->option('debug') ?? false;
        $articleId = $this->option('article_id') ?? null;
        $now = $this->option('now') ? Carbon::parse($this->option('now')) : Carbon::now();

        $timeBefore = $now->floorMinutes(20);
        $timeAfter = (clone $timeBefore)->subMinutes(20);

        $this->line(sprintf("Fetching aggregated pageviews data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));

        $request = new AggregateRequest('pageviews', 'load');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        if ($articleId) {
            $request->addFilter('article_id', $articleId);
        }
        $request->addGroup('article_id', 'signed_in', 'subscriber');
        $records = collect($journalContract->count($request));

        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process, exiting."));
            return 0;
        }

        /** @var ProgressBar $bar */
        $bar = new DebugProxy($this->output->createProgressBar(count($records)), $debug);
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Processing pageviews');

        $all = [];
        $signedIn = [];
        $subscribers = [];

        foreach ($records as $record) {
            if (empty($record->tags->article_id)) {
                $bar->advance();
                continue;
            }
            $bar->setMessage(sprintf("Processing pageviews for article ID: <info>%s</info>", $record->tags->article_id));

            $articleId = $record->tags->article_id;

            $all[$articleId] = $all[$articleId] ?? 0;
            $signedIn[$articleId] = $signedIn[$articleId] ?? 0;
            $subscribers[$articleId] = $subscribers[$articleId] ?? 0;

            $all[$articleId] += $record->count;
            if (filter_var($record->tags->signed_in, FILTER_VALIDATE_BOOLEAN) === true) {
                $signedIn[$articleId] += $record->count;
            }
            if (filter_var($record->tags->subscriber, FILTER_VALIDATE_BOOLEAN) === true) {
                $subscribers[$articleId] += $record->count;
            }

            $bar->advance();
        }
        $bar->finish();
        $this->line("\n<info>Pageviews loaded from Journal API</info>");

        if (count($all) === 0) {
            $this->line(sprintf('No data to store for articles, exiting.'));
            return 0;
        }

        $externalIdsChunks =  array_chunk(array_keys($all), 200);

        /** @var ProgressBar $bar */
        $bar = new DebugProxy($this->output->createProgressBar(count($externalIdsChunks)), $debug);
        $bar->setFormat('%message%: [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Storing aggregated data');

        $processedArticles = 0;
        foreach ($externalIdsChunks as $externalIdsChunk) {
            $articles = Article::whereIn(
                'external_id',
                array_map('strval', $externalIdsChunk)
            )->get();
            foreach ($articles as $article) {
                $ap = ArticlePageviews::firstOrNew([
                    'article_id' => $article->id,
                    'time_from' => $timeAfter,
                    'time_to' => $timeBefore,
                ]);

                $ap->sum = $all[$article->external_id];
                $ap->signed_in = $signedIn[$article->external_id];
                $ap->subscribers = $subscribers[$article->external_id];
                $ap->save();

                $article->pageviews_all = $article->pageviews()->sum('sum');
                $article->pageviews_subscribers = $article->pageviews()->sum('subscribers');
                $article->pageviews_signed_in = $article->pageviews()->sum('signed_in');
                $article->save();
            }
            $processedArticles += count($externalIdsChunk);
            $bar->setMessage(sprintf('Storing aggregated data (<info>%s/%s</info> articles)', $processedArticles, count($all)));
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <info>OK!</info> (number of processed articles: $processedArticles)");
        return 0;
    }
}
