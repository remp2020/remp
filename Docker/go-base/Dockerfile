FROM golang:1.14.1-buster

# ----------------------------------------------------------------------------------------------------------------------
# VERSIONS AND BASIC ENVIRONMENT VARIABLES
# ----------------------------------------------------------------------------------------------------------------------
# Go
ENV SRC_FOLDER="gitlab.com/remp/remp/Beam/go"
ENV FULL_SRC_FOLDER="/go/src/${SRC_FOLDER}"
# Dockerize
ENV DOCKERIZE_VERSION v0.6.1

# ----------------------------------------------------------------------------------------------------------------------
# INITIAL CONFIGURATION
# ----------------------------------------------------------------------------------------------------------------------
RUN apt-get update && \
    apt-get install -y \
        gettext-base && \
    go get golang.org/x/tools/cmd/goimports && \
    apt-get clean

# ----------------------------------------------------------------------------------------------------------------------
# USER
# ----------------------------------------------------------------------------------------------------------------------
RUN addgroup \
        --gid 1000 \
        user && \
    useradd \
        --uid 1000 \
        --gid user \
        --home-dir /home/user \
        --create-home \
        --shell /bin/bash \
        user && \
    sed -i 's/^#alias l/alias l/g' /home/user/.bashrc && \
    mkdir -p \
        ${FULL_SRC_FOLDER} && \
    chown user:user -R \
        ${FULL_SRC_FOLDER}

# ----------------------------------------------------------------------------------------------------------------------
# DOCKERIZE AND RUN SETUP
# ----------------------------------------------------------------------------------------------------------------------
RUN wget --quiet https://github.com/jwilder/dockerize/releases/download/${DOCKERIZE_VERSION}/dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz && \
    tar -C /usr/local/bin -xzvf dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz && \
    rm dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz

USER user

WORKDIR ${FULL_SRC_FOLDER}
