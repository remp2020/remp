FROM alpine

WORKDIR /bin

ADD segments.tar .

ADD .env.example .env

RUN apk add --no-cache openssl

RUN apk add --no-cache tzdata

CMD segments
