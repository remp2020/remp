echo "Starting elasticsearch"
./create-indexes.sh &
/usr/local/bin/docker-entrypoint.sh
