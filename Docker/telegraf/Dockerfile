FROM remp/telegraf:1.35.3

RUN apk update
RUN apk add ca-certificates
RUN update-ca-certificates
RUN apk add openssl

ADD telegraf.conf /etc/telegraf/telegraf.conf

CMD telegraf
