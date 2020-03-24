FROM nginx:1.16.1

ARG DOCKER_USER
ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID

# User

RUN useradd -d /home/user -u 1000 -m -s /bin/bash user && \
    mkdir -p /var/www/html && \
    chown user:user /var/www/html && \
    sed -i -e '/user/!b' -e '/nginx/!b' -e 's/nginx;/user user;/' /etc/nginx/nginx.conf

WORKDIR /var/www/html

ADD bin/fix-user .

RUN ./fix-user ${DOCKER_USER} user ${DOCKER_USER_ID} ${DOCKER_GROUP_ID}

CMD ["/bin/bash", "-c", "envsubst '${NGINX_PORT}' < /etc/nginx/conf.d/default.template > /etc/nginx/conf.d/default.conf && exec nginx -g 'daemon off;'"]
