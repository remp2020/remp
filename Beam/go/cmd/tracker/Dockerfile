FROM alpine

WORKDIR /bin

ADD tracker.tar .

ADD .env.example .env

RUN apk add --no-cache openssl

CMD tracker
