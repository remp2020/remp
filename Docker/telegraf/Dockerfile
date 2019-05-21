FROM remp/telegraf:1.10.4

ENV DOCKERIZE_VERSION v0.6.0

RUN apk update
RUN apk add ca-certificates
RUN update-ca-certificates
RUN apk add openssl
RUN apk add wget

RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

CMD ["dockerize", "-timeout", "1m", "-wait-retry-interval", "10s", "-wait", "tcp://kafka:2181", "-wait", "tcp://kafka:9092", "telegraf"]
