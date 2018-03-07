<?php

namespace App\Factory;

class KafkaFactory
{
    public function getInstance(string $consumerGroup, array $topics): \RdKafka\KafkaConsumer
    {
        $config = new \RdKafka\Conf();
        $config->set('group.id', $consumerGroup);
        $config->set('metadata.broker.list', config('kafka.broker'));

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.offset.reset', 'smallest');
        $config->setDefaultTopicConf($topicConf);

        $consumer = new \RdKafka\KafkaConsumer($config);
        $consumer->subscribe($topics);
        return $consumer;
    }
}
