<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Console\Command;

class AggregatePageviews extends Command
{
    const COMMAND = 'pageviews:aggregate';

    protected $signature = self::COMMAND . ' {--now=} {--debug} {--article_id=}';

    protected $description = 'Runs aggregate pageview timespent/load jobs.';

    public function handle()
    {
        $arguments = [];
        if ($this->option('now')) {
            $arguments['--now'] = $this->option('now');
        }
        if ($this->option('debug')) {
            $arguments['--debug'] = true;
        }
        if ($this->option('article_id')) {
            $arguments['--article_id'] = $this->option('article_id');
        }

        $this->call('pageviews:aggregate-load', $arguments);
        $this->call('pageviews:aggregate-timespent', $arguments);
    }
}
