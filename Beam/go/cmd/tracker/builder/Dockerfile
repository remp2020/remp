FROM golang:1.10

RUN go get golang.org/x/tools/cmd/goimports

RUN mkdir -p /src/build

RUN mkdir -p /go/src/gitlab.com/remp/remp/Beam/go

WORKDIR /go/src/gitlab.com/remp/remp/Beam/go

COPY build /usr/local/bin/

RUN chmod +x /usr/local/bin/build

CMD ["build"]
