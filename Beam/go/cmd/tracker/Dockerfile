FROM alpine

WORKDIR /bin

ADD tracker.tar .

ADD dockerize-alpine-linux-amd64-v0.3.0.tar.gz .

CMD ["dockerize", "-timeout", "1m", "-wait", "tcp://zookeeper:2181", "tracker"]
