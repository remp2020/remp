FROM telegraf:1.3

ADD dockerize-alpine-linux-amd64-v0.4.0.tar.gz .

CMD ["./dockerize", "-timeout", "1m", "-wait-retry-interval", "10s", "-wait", "tcp://kafka:2181", "-wait", "tcp://kafka:9092", "telegraf"]