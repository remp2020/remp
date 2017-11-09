# Campaign

## Admin (Laravel)

Campaign Admin serves as a tool for configuration of banners and campaigns. It's the place for UI generation of banners
and definition of how and to whom display Campaigns. 

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

### Dependencies

- PHP 7.1
- MySQL 5.7
- Redis ^3.2

### Schedule

For application to function properly you need to add Laravel's schedule running into your crontab:

```
* * * * * php artisan schedule:run >> storage/logs/schedule.log 2>&1
```

Laravel's scheduler currently includes:

*CacheSegmentJob*:

- Triggered hourly and forced to refresh.

### Queue

For application to function properly, you also need to have Laravel's queue worker running as a daemon. Please follow the
official documentation's [guidelines](https://laravel.com/docs/5.4/queues#running-the-queue-worker).

```bash
php artisan queue:work
```

Laravel's queue currently includes

*CacheSegmentJob*: 

- Triggered when campaign is activated. 
- Trigerred when cached data got invalidated and need to be fetched again.

If the data are still valid, job doesn't refresh them.