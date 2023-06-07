<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Helpers\Journal\JournalHelpers;
use Carbon\Carbon;
use Remp\BeamModule\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class DashboardRefresh extends Command
{
    const COMMAND = 'dashboard:refresh';

    protected $signature = self::COMMAND;

    protected $description = 'Refresh cached dashboard stats';

    private JournalHelpers $journalHelpers;

    public function __construct(JournalHelpers $journalHelpers)
    {
        parent::__construct();
        $this->journalHelpers = $journalHelpers;
    }

    public function handle()
    {
        $this->line('<info>***** Dashboard refresh (' . Carbon::now()->format(DATE_RFC3339) . ') *****</info>');

        $articlesToRefresh = Article::whereHas('dashboardArticle', function (Builder $query) {
            $query->where('last_dashboard_time', '>=', Carbon::now()->subHour());
        })->get()->keyBy('external_id');

        $this->getOutput()->write("Refreshing <comment>{$articlesToRefresh->count()}</comment> articles: ");
        $uniqueBrowserCounts = $this->journalHelpers->uniqueBrowsersCountForArticles($articlesToRefresh);
        $this->line('OK');

        $this->getOutput()->write('Updating database: ');
        $articlesToUpdate = Article::with('dashboardArticle')
            ->whereIn('external_id', $uniqueBrowserCounts->keys()->filter())
            ->get();

        /** @var Article $article */
        foreach ($articlesToUpdate as $article) {
            $article->dashboardArticle()->updateOrCreate([], [
                'unique_browsers' => (int) $uniqueBrowserCounts[$article->external_id],
            ]);
        }
        $this->line('OK');

        return self::SUCCESS;
    }
}
