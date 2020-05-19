FROM remp-go-base:latest

# ----------------------------------------------------------------------------------------------------------------------
# BASIC ENVIRONMENT VARIABLES
# ----------------------------------------------------------------------------------------------------------------------
ENV APP_EXECUTABLE="tracker" \
    FULL_SRC_FOLDER_APP="${FULL_SRC_FOLDER}/cmd/tracker" \
    GOAGEN_DIR="../../vendor/github.com/goadesign/goa/goagen" \
    DESIGN_PKG="${SRC_FOLDER}/cmd/tracker/design"
ENV PATH="${FULL_SRC_FOLDER_APP}:${PATH}"

# ----------------------------------------------------------------------------------------------------------------------
# RUN
# Run setup, variables and dockerize start
# ----------------------------------------------------------------------------------------------------------------------
WORKDIR ${FULL_SRC_FOLDER_APP}

EXPOSE 8081

ADD entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["dockerize", "-timeout", "1m", "-wait-retry-interval", "10s", "-wait", "tcp://zookeeper:2181", "-wait", "tcp://mysql:3306", "tracker"]
