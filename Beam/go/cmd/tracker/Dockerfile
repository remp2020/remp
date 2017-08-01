FROM alpine

WORKDIR /bin

ADD tracker.tar .

ADD .env.example .env

ADD dockerize-alpine-linux-amd64-v0.5.0.tar.gz .

CMD ["dockerize", "-timeout", "1m", "-wait", "tcp://kafka:2181", "tracker"]
