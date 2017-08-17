<?php

namespace Remp\MailerModule\Broker;

use Kafka\ConsumerConfig;

class ConsumerFactory
{
    const CONSUMER_GROUP = 'remp_mailer';

    /** @var ConsumerConfig */
    private $config;

    public function __construct($kafkaBroker)
    {
        $this->config = new \RdKafka\Conf();
        $this->config->set('group.id', self::CONSUMER_GROUP);
        $this->config->set('metadata.broker.list', $kafkaBroker);

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.offset.reset', 'smallest');
        $this->config->setDefaultTopicConf($topicConf);
    }

    public function getInstance($topics)
    {
        $consumer = new \RdKafka\KafkaConsumer($this->config);
        $consumer->subscribe($topics);
        return $consumer;
    }
}