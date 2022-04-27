FROM golang:1.18.1

RUN go install golang.org/x/tools/cmd/goimports@latest

RUN mkdir -p /src/build

RUN mkdir -p /go/src/gitlab.com/remp/remp/Beam/go

WORKDIR /go/src/gitlab.com/remp/remp/Beam/go

COPY build /usr/local/bin/

RUN chmod +x /usr/local/bin/build

CMD ["build"]
