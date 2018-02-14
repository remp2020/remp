<?php

namespace App\Console\Commands;

use App\Conversion;
use App\Factory\KafkaFactory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReadCommerceEventsCommand extends Command
{
    const CONSUMER_GROUP = 'beam-conversion-listener';

    protected $signature = 'listen:conversions';

    protected $description = 'Attaches to Kafka commerce_purchase topic and creates internal conversions';

    protected $kafkaFactory;

    public function __construct(KafkaFactory $kafkaFactory)
    {
        parent::__construct();
        $this->kafkaFactory = $kafkaFactory;
    }

    public function handle()
    {
        $this->line('');
        $this->line('<info>***** Conversions Listener *****</info>');
        $this->line('');

        $consumer = $this->kafkaFactory->getInstance(self::CONSUMER_GROUP, ['commerce_purchase']);

        while (true) {
            $message = $consumer->consume(120000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    continue 2;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    continue 2;
                default:
                    throw new \Exception($message->errstr(), $message->err);
            }

            $message = json_decode($message->payload);
            if (!$message->article || !$message->article->id) {
                continue;
            }

            $this->info(sprintf("Processing transaction: %s", $message->purchase->transaction_id));

            Conversion::firstOrCreate([
                'transaction_id' => $message->purchase->transaction_id
            ], [
                'transaction_id' => $message->purchase->transaction_id,
                'amount' => $message->purchase->revenue->amount,
                'currency' => $message->purchase->revenue->currency,
                'paid_at' => new Carbon($message->system->time),
                'article_external_id' => $message->article->id,
            ]);
        }
    }
}
