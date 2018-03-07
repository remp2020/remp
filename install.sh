#!/bin/bash
cd Beam
make docker-build
cd ..
rm -f Beam/.env
rm -f Mailer/.env
rm -f Mailer/config/config.local.neon
rm -f Sso/.env
docker-compose up -d
