<?php

namespace Remp\BeamModule\Jobs;

use Remp\BeamModule\Model\Conversion;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversion;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->chain([
            new AggregateConversionEventsJob($this->conversion),
            new ProcessConversionSourcesJob($this->conversion)
        ]);
    }
}
