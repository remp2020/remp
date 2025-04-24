# Campaign

Campaign is a simple tool for creation and management of banner campaigns on your web.

* Admin
  * [Integration with CMS/CRM](#admin-integration-with-cmscrm)
    * [Javascript snippet](#javascript-snippet)
    * [Segment integration](#segment-integration)
  * [Integration with Beam Journal](#admin-integration-with-beam-journal)

## Admin (Laravel)

Campaign Admin serves as a tool for configuration of banners and campaigns. It's the place for UI generation of banners
and definition of how and to whom display Campaigns. 

When the backend is ready, don't forget to create `.env` file (use `.env.example` as boilerplate), install dependencies and run DB migrations:

```bash
# 1. Download PHP dependencies
composer install

# 2. Download JS/HTML dependencies
yarn install

# !. use extra switch if your system doesn't support symlinks (Windows; can be enabled)
yarn install --no-bin-links

# 3. Generate assets
yarn run all-dev // or any other alternative defined within package.json

# 4. Run migrations
php artisan migrate

# 5. Generate app key
php artisan key:generate

# 6. Run seeders (optional)
php artisan db:seed
```

### Dependencies

- PHP ^8.2
- MySQL ^8.0
- Redis ^6.2
- Node.js >=18

#### Redis Sentinel

Application supports Redis to be configured with the Sentinel cluster. In order to enable the integration, see `.env.example` file and `REDIS_SENTINEL_SERVICE` variable.

### Deployment

#### Commands

For application to function properly you need to run `php artisan campaigns:refresh-cache` command every time the application change is deployed or application configuration (such as `.env`) is changed.

#### Schedule

For application to function properly you need to add Laravel's schedule running into your crontab:

```
* * * * * php artisan schedule:run >> storage/logs/schedule.log 2>&1
```

Laravel's scheduler currently includes:

*CacheSegmentJob*:

- Triggered hourly and forced to refresh cache segments.

*AggregateCampaignStats*:

- Triggered every minute, saves statistics about ongoing campaings from Beam Journal (if configured).

#### Queue

For application to function properly, you also need to have Laravel's queue worker running as a daemon. Please follow the
official documentation's [guidelines](https://laravel.com/docs/5.4/queues#running-the-queue-worker).

```bash
php artisan queue:work
```

### Admin integration with Beam Journal

Beam Journal API (also known as Segments API) provides API for retrieving information about ongoing campaigns.
Its integration with Campaign tool is optional, but provides ability to see campaign statistics directly in the Campaign admin interface.

Information on how to set up Journal API can be found in the [documentation](../Beam/go/cmd/segments/README.md) or in the REMP installation [guidelines](https://gist.github.com/rootpd/9f771b5a5bbb0b0d9a70321cec710511#beam). 

Once Journal API is running, you can enable its integration by pointing `REMP_SEGMENTS_ADDR` to Journal API URL in `.env` configuration file.

Laravel's queue currently includes

*CacheSegmentJob*: 

- Triggered when campaign is activated. 
- Trigerred when cached data got invalidated and need to be fetched again.

If the data are still valid, job doesn't refresh them.
