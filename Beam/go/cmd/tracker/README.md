# REMP Tracker

This is a gateway for storing both user and system events. Tracker validates the request and posts Influx-formatted
set of data to a message broker implementation (either Kafka or Pub/Sub).

## Building

### docker

If you have docker installed, you can run

```bash
make docker-build
``` 

which will generate tarball containing *amd64* targetted
build with *swagger.json*.

This tarball can be safely used within any Docker image, including `alpine` or `busybox`.

### go

If you have Go 1.23+ environment set up, you can run the build manually by running

```bash
make all
```

## Running

Once you have your environment variables set or `.env` file ready, run the built binary.

### .env variables

variable|example value
--- | ---
TRACKER_ADDR|`:8081`
TRACKER_BROKER_ADDR|`kafka:9092`
TRACKER_DEBUG|`true`
TRACKER_MYSQL_NET|`tcp`
TRACKER_MYSQL_ADDR|`mysql:3306`
TRACKER_MYSQL_DBNAME|`beam`
TRACKER_MYSQL_USER|`root`
TRACKER_MYSQL_PASSWD|`secret`
TRACKER_TIMESPENT_LIMIT|`3600`
