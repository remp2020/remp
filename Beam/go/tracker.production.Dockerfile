########################################################################################################################
# Golang base image for next stages

FROM golang:1.13.6-buster as golang_base

ENV SRC_FOLDER="gitlab.com/remp/remp/Beam/go"
ENV FULL_SRC_FOLDER="/go/src/${SRC_FOLDER}"

RUN apt update && \
    apt install -y \
        gettext-base && \
    go get golang.org/x/tools/cmd/goimports && \
    useradd -d /home/user -u 1000 -m -s /bin/bash user && \
    mkdir -p ${FULL_SRC_FOLDER} && \
    chown user:user ${FULL_SRC_FOLDER} && \
    apt clean

USER user

WORKDIR ${FULL_SRC_FOLDER}

########################################################################################################################
# Tracker production build stage

FROM golang_base as tracker_production

ENV TRACKER_ADDR=0.0.0.0:8081 \
    TRACKER_DEBUG=true \
    TRACKER_BROKER_ADDRS=kafka:9092 \
    TRACKER_INTERNAL_HOSTS= \
    TRACKER_MYSQL_NET=tcp \
    TRACKER_MYSQL_ADDR=mysql:3306 \
    TRACKER_MYSQL_DBNAME=beam \
    TRACKER_MYSQL_USER=root \
    TRACKER_MYSQL_PASSWD=secret

ENV APP_EXECUTABLE="tracker"
ENV FULL_SRC_FOLDER_APP="${FULL_SRC_FOLDER}/cmd/tracker"
ENV GOAGEN_DIR="../../vendor/github.com/goadesign/goa/goagen" \
    DESIGN_PKG="${SRC_FOLDER}/cmd/tracker/design"

COPY --chown=user:user . .

WORKDIR ${FULL_SRC_FOLDER_APP}

RUN cd ${GOAGEN_DIR} && \
    go build && \
    cd ${FULL_SRC_FOLDER_APP} && \
 	${GOAGEN_DIR}/goagen app -d ${DESIGN_PKG} && \
 	${GOAGEN_DIR}/goagen swagger -d ${DESIGN_PKG} && \
 	rm ${GOAGEN_DIR}/goagen && \
 	rm -fr goagen* && \
    CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o ${APP_EXECUTABLE}

EXPOSE 8081

CMD ["/bin/bash", "-c", "envsubst <.env.dist >.env && ./${APP_EXECUTABLE}"]
