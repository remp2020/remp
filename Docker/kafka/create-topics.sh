#!/bin/bash

START_TIMEOUT=600

start_timeout_exceeded=false
count=0
step=10
while netstat -lnt | awk '$4 ~ /:'$ADVERTISED_PORT'$/ {exit 1}'; do
    echo "waiting for kafka to be ready"
    sleep $step;
    count=$(expr $count + $step)
    if [ $count -gt $START_TIMEOUT ]; then
        start_timeout_exceeded=true
        break
    fi
done

if $start_timeout_exceeded; then
    echo "Not able to auto-create topic (waited for $START_TIMEOUT sec)"
    exit 1
fi

if [[ -n $CREATE_TOPICS ]]; then
    IFS=','; for topicToCreate in $CREATE_TOPICS; do
        echo "creating topics: $topicToCreate"
        IFS=':' read -a topicConfig <<< "$topicToCreate"
        if [ ${topicConfig[3]} ]; then
          JMX_PORT='' /opt/kafka_2.11-0.10.1.0/bin/kafka-topics.sh --create --zookeeper kafka:2181 --replication-factor ${topicConfig[2]} --partition ${topicConfig[1]} --topic "${topicConfig[0]}" --config cleanup.policy="${topicConfig[3]}"
        else
          JMX_PORT='' /opt/kafka_2.11-0.10.1.0/bin//kafka-topics.sh --create --zookeeper kafka:2181 --replication-factor ${topicConfig[2]} --partition ${topicConfig[1]} --topic "${topicConfig[0]}"
        fi
        echo "$topicToCreate created"
    done
fi