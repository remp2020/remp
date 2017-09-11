FROM golang:1.9

RUN go get golang.org/x/tools/cmd/goimports

RUN go get gopkg.in/yaml.v2

RUN go get github.com/Sirupsen/logrus

RUN mkdir -p /src/build

RUN mkdir -p /go/src/gitlab.com/remp/remp/Beam/go

WORKDIR /go/src/gitlab.com/remp/remp/Beam/go

COPY build /usr/local/bin/

RUN chmod +x /usr/local/bin/build

CMD ["build"]
