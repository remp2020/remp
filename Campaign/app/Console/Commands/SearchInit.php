<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SearchInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initial indexing of all searchable models';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting indexing of searchable models');

        \Artisan::call('scout:import', ['model' => 'App\Campaign']);
        \Artisan::call('scout:import',['model' => 'App\Banner']);

        $this->info('Indexing finished');
    }
}
