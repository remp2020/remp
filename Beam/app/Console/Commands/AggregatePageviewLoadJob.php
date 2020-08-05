<?php

namespace App\Console\Commands;

use App\Article;
use App\ArticlePageviews;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;

class AggregatePageviewLoadJob extends Command
{
    const COMMAND = 'pageviews:aggregate-load';

    protected $signature = self::COMMAND . ' {--now=}';

    protected $description = 'Reads pageview/load data from journal and stores aggregated data';

    public function handle(JournalContract $journalContract)
    {
        $now = $this->option('now') ? Carbon::parse($this->option('now')) : Carbon::now();
        $timeBefore = $now->minute(0)->second(0);
        $timeAfter = (clone $timeBefore)->subHour();

        $this->line(sprintf("Fetching aggregated pageviews data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));

        $request = new AggregateRequest('pageviews', 'load');
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id', 'signed_in', 'subscriber');

        $records = collect($journalContract->count($request));

        if (count($records) === 0 || (count($records) === 1 && !isset($records[0]->tags->article_id))) {
            $this->line(sprintf("No articles to process, exiting."));
            return;
        }

        $bar = $this->output->createProgressBar(count($records));
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
            if ($record->tags->signed_in === '1') {
                $signedIn[$articleId] += $record->count;
            }
            if ($record->tags->subscriber === '1') {
                $subscribers[$articleId] += $record->count;
            }

            $bar->advance();
        }
        $bar->finish();
        $this->line(' <info>OK!</info>');

        if (count($all) === 0) {
            $this->line(sprintf("No data to store for articles, exiting."));
            return;
        }

        $allChunks =  array_chunk($all, 200, true);

        $bar = $this->output->createProgressBar(count($allChunks));
        $bar->setFormat('%message%: [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Storing aggregated data');

        $processedUsers = 0;

        foreach ($allChunks as $allChunk) {
            $processedUsers += count($allChunk);

            $bar->setMessage(sprintf('Storing aggregated data (<info>%s/%s</info> articles)', $processedUsers, count($all)));
            $bar->advance();
            $articles = Article::whereIn('external_id', array_keys($allChunk))->get();
            foreach ($articles as $article) {
                $ap = ArticlePageviews::firstOrNew([
                    'article_id' => $article->id,
                    'time_from' => $timeAfter,
                    'time_to' => $timeBefore,
                ]);

                $ap->sum = $all[$articleId];
                $ap->signed_in = $signedIn[$articleId];
                $ap->subscribers = $subscribers[$articleId];
                $ap->save();

                $article->pageviews_all = $article->pageviews()->sum('sum');
                $article->pageviews_subscribers = $article->pageviews()->sum('subscribers');
                $article->pageviews_signed_in = $article->pageviews()->sum('signed_in');
                $article->save();
            }
        }

        $bar->finish();
        $this->line(' <info>OK!</info>');
    }
}
