# The reason for this is that docker-compose on Windows is picky and didn't want to include telegraf.conf via
# docker-compose volume directive via relative path. As I didn't want to put explicit full path of my environment
# here, using separate Dockerfile is probably the cleanest possible solution until this is fixed.

FROM telegraf:1.2-alpine

ADD telegraf.conf /etc/telegraf/

WORKDIR /bin

ADD dockerize-alpine-linux-amd64-v0.3.0.tar.gz .

# TODO: wait until beam_events is ready

CMD ["dockerize", "-timeout", "1m", "-wait", "tcp://zookeeper:2181", "-wait", "tcp://influxdb:8086", "telegraf"]