FROM alpine

WORKDIR /bin

ADD segments.tar .

ADD .env.example .env

RUN apk add --no-cache openssl

ENV DOCKERIZE_VERSION v0.6.0

RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

CMD ["dockerize", "-timeout", "1m", "-wait-retry-interval", "10s", "-wait", "tcp://influxdb:8086", "-wait", "tcp://mysql:3306", "segments"]
