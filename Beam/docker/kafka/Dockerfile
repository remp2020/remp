FROM ches/kafka

USER kafka

WORKDIR /kafka/bin

ADD dockerize-alpine-linux-amd64-v0.3.0.tar.gz .

ADD kafka-start.sh .

ADD create-topics.sh .

WORKDIR /kafka

CMD ["dockerize", "-timeout", "1m", "-wait", "tcp://zookeeper:2181", "kafka-start.sh"]