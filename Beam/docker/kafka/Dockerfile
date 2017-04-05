FROM ches/kafka

WORKDIR /kafka/bin

ADD dockerize-alpine-linux-amd64-v0.3.0.tar.gz .

ADD kafka-start.sh .

ADD create-topics.sh .

USER root

RUN chown kafka:kafka kafka-start.sh create-topics.sh

RUN chmod 755 kafka-start.sh create-topics.sh

USER kafka

WORKDIR /kafka

CMD ["dockerize", "-timeout", "1m", "-wait", "tcp://zookeeper:2181", "kafka-start.sh"]