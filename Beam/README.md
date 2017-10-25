# Beam

## Admin (Laravel)

Beam Admin serves as a tool for configuration of sites, properties and segments. It's the place to get tracking snippets
and manage metadata about your websites.

When the backend is ready, don't forget to install dependencies and run DB migrations:

```bash
# 1. Download PHP dependencies
composer install

# 2. Download JS/HTML dependencies
yarn install

# !. use extra switch if your system doesn't support symlinks (Windows; can be enabled)
yarn install --no-bin-links

# 3. Generate assets
yarn run dev // or any other alternative defined within package.json

# 4. Run migrations
php artisan migrate
```

#### Dependencies

- PHP 7.1
- MySQL 5.7
- Redis 3.2

## [Tracker](go/cmd/tracker) (Go)

Beam Tracker serves as a tool for tracking events via API. Endpoints can be discovered via generated `/swagger.json`.

Tracker pushes events to Kafka in Influx format into `beam_events` topic. For all `/track/event` calls, Tracker also
pushes raw JSON event to its own topic based on the event *category* and *action*.

For example for payload

```json
{
  "category": "foo",
  "action": "bar"
  // ...
}
```

the event would be stored within `foo_bar` topic so everyone can subscribe to it.

#### Dependencies

- Go ^1.8
- Kafka ^0.10
- Zookeeper ^3.4
- MySQL ^5.7
    
## [Segments](go/cmd/segments) (Go)

Beam Segments serves as a read-only API for getting information about segments and users of these segments.
Endpoints can be discovered via generated `/swagger.json`.

#### Dependencies

- Go ^1.8
- InfluxDB ^1.2
- MySQL ^5.7

## [Telegraf](../Docker/telegraf)

Influx Telegraf is a backend service for moving data out of Kafka to InfluxDB. It needs to be ready as Segments are
dependent on Influx-based data pushed by Telegraf.

## [Kafka](../Docker/kafka)

All tracked events are also pushed to Kafka 