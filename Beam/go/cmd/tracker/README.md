# REMP Tracker

This is a gateway for storing both user and system events. Tracker validates the request and posts InfluxDB-formatted
set of data to Kafka. For generic events, it also creates a Kafka topic based on provided `$category_$action`.

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

If you have Go 1.8+ environment set up, you can run the build manually by running

```bash
make build
```

As a dependency management tool we're using `github.com/golang/dep`. We also commit all dependencies into the
repository, so you don't need to rely on our dependency tool of choice. However if you make a commit updating
the dependency, please make sure to run also `dep ensure -update` to keep stuff consistent.

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
TRACKER_INFLUX_ADDR|`http://influxdb:8086`
TRACKER_INFLUX_DBNAME|`beam`
TRACKER_INFLUX_USER|`root`
TRACKER_INFLUX_PASSWD|`secret`
