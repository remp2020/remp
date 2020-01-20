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
# Segments production build stage

FROM golang_base as segments_production

ENV SEGMENTS_ADDR=0.0.0.0:8082 \
    SEGMENTS_DEBUG=true \
    SEGMENTS_URL_EDIT=http://beam.remp.press/segments/{segment_id}/edit \
    SEGMENTS_MYSQL_NET=tcp \
    SEGMENTS_MYSQL_ADDR=mysql:3306 \
    SEGMENTS_MYSQL_DBNAME=beam \
    SEGMENTS_MYSQL_USER=root \
    SEGMENTS_MYSQL_PASSWD=secret \
    SEGMENTS_ELASTIC_ADDRS=http://elasticsearch:9200 \
    SEGMENTS_ELASTIC_USER=elastic \
    SEGMENTS_ELASTIC_PASSWD=

ENV APP_EXECUTABLE="segments"
ENV FULL_SRC_FOLDER_APP="${FULL_SRC_FOLDER}/cmd/segments"
ENV GOAGEN_DIR="../../vendor/github.com/goadesign/goa/goagen" \
    DESIGN_PKG="${SRC_FOLDER}/cmd/segments/design"

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
    
EXPOSE 8082

CMD ["/bin/bash", "-c", "envsubst <.env.dist >.env && ./${APP_EXECUTABLE}"]
