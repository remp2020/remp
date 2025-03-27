# REMP Segments/Journal

Segments (also known as Journal) is read-only API to acquire aggregations over the tracked data. 

It provides:

- Segment API for checking members of segments.
- Journal API for acquiring raw events and aggregated statistics about events.

## Dependencies

- Go ^1.22
- Elasticsearch ^7.15 (even though any 7.* should be OK)
- MySQL ^8.0

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
SEGMENTS_INDEX_PREFIX|`test-`
