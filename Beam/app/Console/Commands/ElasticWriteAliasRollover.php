<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;

class ElasticWriteAliasRollover extends Command
{
    const COMMAND = 'service:elastic-write-alias-rollover';

    protected $signature = self::COMMAND . ' {--host=} {--write-alias=} {--read-alias=}';

    protected $description = 'Rollover write index and assign newly created index to read index';

    public function handle()
    {
        if (!$this->input->getOption('host')) {
            $this->line('<error>ERROR</error> You need to provide <info>--host</info> option with address to your Elastic instance (e.g. <info>--host=http://localhost:9200</info>)');
            return;
        }
        if (!$this->input->getOption('write-alias')) {
            $this->line('<error>ERROR</error> You need to provide <info>--write-alias</info> option with name of the write alias you use (e.g. <info>--write-alias=pageviews_write</info>)');
            return;
        }
        if (!$this->input->getOption('read-alias')) {
            $this->line('<error>ERROR</error> You need to provide <info>--read-alias</info> option with name of the read alias you use (e.g. <info>--read-alias=pageviews</info>)');
            return;
        }
        
        $client = new Client([
            'base_uri' => $this->input->getOption('host'),
        ]);

        $this->line(sprintf(
            "Executing rollover for <info>%s/%s</info>:",
            $this->input->getOption('host'),
            $this->input->getOption('write-alias')
        ));

        // execute rollover; https://www.elastic.co/guide/en/elasticsearch/reference/6.3/indices-rollover-index.html
        try {
            $response = $client->post(sprintf("/%s/_rollover", $this->input->getOption('write-alias')), [
                'json' => [
                    'conditions' => [
                    'max_size' => '4gb',
//                        'max_docs' => 1, // condition for testing
                    ],
                ],
            ]);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody());
            dump($body);
            return;
        }


        $body = json_decode($response->getBody(), true);
        if (!$body['rolled_over']) {
            $this->line('  * Condition not matched, done.');
            return;
        }

        $this->line(sprintf(
            '  * Rolled over, adding newly created <info>%s</info> to alias <info>%s</info>.',
            $body['new_index'],
            $this->input->getOption('read-alias')
        ));

        // if rollover happened, add newly created index to the read alias (so it contains all the indices)
        try {
            $client->post("/_aliases", [
                'json' => [
                    'actions' => [
                        'add' => [
                            'index' => $body['new_index'],
                            'alias' => $this->input->getOption('read-alias')
                        ],
                    ],
                ],
            ]);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody());
            dump($body);
            return;
        }

        $this->line('  * Alias created, done.');
    }
}
