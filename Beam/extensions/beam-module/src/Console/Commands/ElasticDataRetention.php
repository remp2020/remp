<?php

namespace Remp\BeamModule\Console\Commands;

use Illuminate\Support\Str;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Remp\BeamModule\Console\Command;

class ElasticDataRetention extends Command
{
    const COMMAND = 'service:elastic-data-retention';

    protected $signature = self::COMMAND . ' {--host=} {--match-index=} {--date=} {--auth=}';

    protected $description = 'Data retention tries to find index based on match-index and date options and removes it';

    public function handle()
    {
        if (!$this->input->getOption('host')) {
            $this->line('<error>ERROR</error> You need to provide <info>--host</info> option with address to your Elastic instance (e.g. <info>--host=http://localhost:9200</info>)');
            return 1;
        }
        if (!$this->input->getOption('match-index')) {
            $this->line('<error>ERROR</error> You need to provide <info>--match-index</info> option with name of the index you want to cleanup (e.g. <info>--write-alias=pageviews_write</info>)');
            return 1;
        }
        if (!$this->input->getOption('date')) {
            $this->line('<error>ERROR</error> You need to provide <info>--date</info> option with date that will be searched within index name (e.g. <info>--date="90 days ago"</info>)');
            return 1;
        }

        $date = new Carbon($this->input->getOption('date'));
        $client = new Client([
            'base_uri' => $this->input->getOption('host'),
        ]);

        $this->line('');
        $this->info('**** ' . self::COMMAND . ' (date: ' . (new Carbon())->format(DATE_RFC3339) . ') ****');
        
        $targetIndices = sprintf(
            "/%s*%s*",
            $this->input->getOption('match-index'),
            $date->format('Y.m.d')
        );

        $this->line(sprintf(
            "Executing data retention for <info>%s%s</info> (date: %s)",
            $this->input->getOption('host'),
            $targetIndices,
            $this->input->getOption('date')
        ));
        
        // Return status 404 in case wildcard match doesn't find an index to delete
        $targetIndices .= '?allow_no_indices=false';

        $options = [];
        if ($this->input->getOption('auth')) {
            $auth = $this->input->getOption('auth');
            if (!Str::contains($auth, ':')) {
                $this->line("<error>ERROR</error> You need to provide <info>--auth</info> option with a name and a password (to Elastic instance) separated by ':', e.g. admin:password");
                return 1;
            }

            [$user, $pass] = explode(':', $auth, 2);
            $options = [
                'auth' => [$user, $pass]
            ];
        }

        // execute index delete; https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
        try {
            $client->delete($targetIndices, $options);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody());
            if ($e->getCode() === 404 && $body->error->type ?? '' === 'index_not_found_exception') {
                $this->line('  * No index to delete.');
                return 0;
            }
             
            $this->line("<error>ERROR</error> Client exception: " . $e->getMessage());
            return 2;
        }

        $this->line('  * Done, index deleted.');
        return 0;
    }
}
