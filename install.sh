#!/bin/bash

rm -f Beam/.env
rm -f Mailer/.env
rm -f Mailer/config/config.local.neon
rm -f Sso/.env

if [ ! -f ".env.docker" ]; then
    cp .env.docker.dist .env.docker
fi

sed -i 's/\(^DOCKER_USER=\).*/\1'$(id -u -n)'/g' .env.docker
sed -i 's/\(^DOCKER_USER_ID=\).*/\1'$(id -u)'/g' .env.docker
sed -i 's/\(^DOCKER_GROUP_ID=\).*/\1'$(id -g)'/g' .env.docker

make docker-go-base-build

docker-compose --env-file=.env.docker up --build -d
