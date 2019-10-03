# REMP Segments/Journal

Segments (also known as Journal) is read-only API to acquire information about segments defined via web administration interface. 

It provides:

- Segment API for checking members of segments.
- Journal API for acquiring raw events and aggregated statistics about events.

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
SEGMENTS_ADDR|`:8082`
SEGMENTS_DEBUG|`true`
SEGMENTS_MYSQL_NET|`tcp`
SEGMENTS_MYSQL_ADDR|`mysql:3306`
SEGMENTS_MYSQL_DBNAME|`beam`
SEGMENTS_MYSQL_USER|`root`
SEGMENTS_MYSQL_PASSWD|`secret`
SEGMENTS_ELASTIC_ADDR|`http://elasticsearch:9200`
SEGMENTS_ELASTIC_USER|`elastic`
SEGMENTS_ELASTIC_PASSWD|`secret`
