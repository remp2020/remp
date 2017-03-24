# The reason for this is that docker-compose on Windows is picky and didn't want to include telegraf.conf via
# docker-compose volume directive via relative path. As I didn't want to put explicit full path of my environment
# here, using separate Dockerfile is probably the cleanest possible solution until this is fixed.

FROM telegraf:1.2-alpine

ADD telegraf.conf /etc/telegraf/