# Beam

## Running

Suite can be run via Docker and Docker Compose. The appliance was tested with Docker CE 2017.03.
 
    make docker-compose
    docker-compose up

You can run `docker-compose ps` to see exposed ports of the appliance:

- Admin: `:8080`
- Tracker `:8081`, and you can visit `:8081/swagger.json` for the full API specs

## Admin (Laravel)

Beam Admin serves as a tool for configuration of sites and properties. It's the place to generate tracking snippets
and manage metadata about your websites. When the backend is ready, don't forget to install dependencies and run 
DB migrations:

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

### Dependencies

- PHP 7.1
- MySQL 5.7

## Tracker (Go)

Beam Tracker serves as a tool for tracking events via API. Endpoints can be discovered via generated swagger.json.

To build it, you need to have Go 1.8 installed and any kind of GNU make support (Unix-based or 
CygWin on Windows). After that, execute:

    make build
    
If you don't want to install Go, you can fully use Docker to run all tools together in the root of the project. 
Feel free to alter `docker-compose.yml` based on your needs.

### Dependencies

- Go 1.8
- InfluxDB 1.2
- Telegraf 1.2
- Kafka 0.10
- Zookeeper 3.4
