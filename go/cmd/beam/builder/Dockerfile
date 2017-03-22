FROM golang:1.8

RUN mkdir -p /src/build

RUN mkdir -p /go/src/gitlab.com/remp/remp/go

WORKDIR /go/src/gitlab.com/remp/remp/go

COPY build /usr/local/bin/

RUN chmod +x /usr/local/bin/build

CMD ["build"]
