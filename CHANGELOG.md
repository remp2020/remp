# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Sso]

- Upgraded to Laravel 6. remp/remp#491

### [Beam]

- Added `TagCategory` filter option to `/top` APIs. remp/remp#898
- Fixed issue with article scroll progress tracking if `article.elementFn` callback wasn't set or didn't return any element. 

### [Campaign]

- **BREAKING**: Moved key used for segment caching from `CacheSegmentJob` into `SegmentAggregator`. remp/crm#1765
- Added API to temporary override user's presence in cached segment (next scheduled cache job loads list against segment query). remp/crm#1765
- Changed the format of stored tracking parameters in cookies and local storage. remp/remp#889

### [Mailer]

- Removed unused table `hermes_tasks_old` created as backup when Hermes was updated to v2.1 _(see `HermesRetry` migration; commit [5fcd07ff](https://github.com/remp2020/remp/commit/5fcd07ffdda658334b0b990252eb94af0857b894))_.
- Added mail job stats updating to `MailgunEventHandler`. Every suitable Mailgun event is processed and corresponding column in `mail_job_batch_templates` updated. remp/remp#853
- Added `only-converted` option to `ProcessJobStatsCommand` to run command to update only `converted` column in `mail_job_batch_templates` table. remp/remp#853

## [0.23.0] - 2021-05-12

### Project

- Fixed possible UI flaws caused by select pickers overflowing if the content is too wide. remp/remp#781

### [Beam]

- Upgraded to Laravel 6. remp/remp#491
- **BREAKING**: Environment variable `QUEUE_DRIVER` changed to `QUEUE_CONNECTION`, please update your `.env` file accordingly. remp/remp#491
- Fixed possibility of missing data in the Segments API unique count aggregation if the aggregation was not fully resolved - if one of the groupped fields was not set in the raw data. remp/remp#902
- Fixed missing `Access-Control-Allow-Headers` header in preflight request causing IOTA loading issues. remp/remp#905 
- Added option to specify `tags` parameter in `/api/articles/top` and `/api/authors/top` API endpoints via `name` or `external_id` parameters. remp/remp#897
- Added `TagCategory` to categorize `Tags`. Added support for `TagCategories` to `/api/v2/articles/upsert` API. remp/remp#898

### [Campaign]

- **BREAKING:** Environment variable QUEUE_DRIVER changed to QUEUE_CONNECTION, please update your .env file accordingly. remp/remp#491
- Upgraded Laravel version to 5.8. remp/remp#491
- Added option to set collapsible bar template as inline. remp/remp#906

### [Mailer]

- Fixed incorrect `updated_at` setting when subscribing user to the newsletter and updating newsletter. remp/remp#896
- Added `page_url` to `ListForm` to set frontend URL where information about newsletter (with past editions) is available. remp/remp#882
- Added separate configuration for tests, so we can guarantee tests reproducibility. remp/remp#890
- Added `context` and `mail_type_variant_id` parameters to `MailJobCreateApiHandler`. remp/remp#890
- Added API endpoint `/api/v1/mailers/mail-type-variants` to create new mail type variants. See [README.md](./Mailer/README.md) for more details. remp/remp#890
- Fixed "copy" feature of mail templates broken since internal changes in `0.20.0`. remp/crm#1889
- Changed type of `payload` column in `hermes_tasks` error logging table to `mediumtext` to avoid issues with trimmed payloads in case the hermes message was bigger. remp/crm#1891

### [Sso]

- **BREAKING:** Environment variable QUEUE_DRIVER changed to QUEUE_CONNECTION, please update your .env file accordingly. remp/remp#491
- Upgraded Laravel version to 5.8. remp/remp#491

## [0.22.0] - 2021-04-28

### Project

- **BREAKING**: Bumping minimal version of PHP to 7.4. Version is enforced by Composer and any older version is not allowed anymore. Please upgrade PHP accordingly.

### [Beam]

- Added support for Sentry error logging. Airbrake is becoming obsolete and will be dropped in the future releases. remp/remp#888

### [Campaign]

- Added support for Sentry error logging. Airbrake is becoming obsolete and will be dropped in the future releases. remp/remp#888
- Fixed segment caching if there's an issue with listing of segment. remp/remp#891

### [Mailer]

- Added `email` parameter to templates. Previously it could have been available by returning it via `IUser` interface in jobs, from now it's available everywhere. remp/remp#880
- Fixed slow mailgun events hermes processing by adding missing `mail_sender_id` index. remp/remp#881
- Added attribute `autocomplete=off` to `start_at` input field in `NewBatchForm`. remp/remp#854
- Added `save_start` submit button to `NewBatchForm` to create new mail job batch and set its status to `ready`. remp/remp#855
- Changed `MailWorkerCommand` to clean `mail_job_queue` table after all batch mail jobs are done. This change should help with email sending issues caused by possible database deadlock. remp/remp#886

### [Sso]

- Added support for Sentry error logging. Airbrake is becoming obsolete and will be dropped in the future releases. remp/remp#888
- Added documentation for `JWT_EMAIL_PATTERN_WHITELIST` variable, fixed how email is validated against listed domains. remp/remp#848

## [0.21.5] - 2021-04-13

### [Beam]

- Fixed pageviews and time spent stats in tags listing. remp/remp#776

### [Mailer]

- Fixed unresolved `settings` and `unsubscribe` template variables if system was configured to use Mailgun mailer with batch sending. Please be aware that there's still known issue that the links do not receive tracking (RTM) params if batch sending is used. remp/remp#879

## [0.21.4] - 2021-04-07

### [Mailer]

- Changed `MailgunEventHandler` to emit `email-dropped` message when event `failed` with `severity=permanent` occurs. remp/remp#868
- Fixed new email create bug throwing an error if "click tracking" was selected to non-default value. remp/remp#875

## [0.21.3] - 2021-04-06

### [Mailer]

- Fixed incompatible types warning in `SnippetsRepository`. remp/remp#874

## [0.21.2] - 2021-04-06

### [Beam]

- Fixed broken aggregaton of pageviews data which didn't correctly aggregate anonymous pageview and timespent counts/sums. remp/remp#869
  - Based on your current data retention setup, it's recommended to reaggregate the data after you release the segments API fix (replace 1000 with the actual number of hours you want to reaggregate):
    ```
    for i in {0..1000} ; do php artisan pageviews:aggregate-load --now="$i hours ago" ; done
    for i in {0..1000} ; do php artisan pageviews:aggregate-timespent --now="$i hours ago" ; done
    ```

### [Mailer]

- Changed `MissingConfiguration` component to check configurations of all mailers used as sending mailers in newsletter lists besides default mailer. remp/remp#858
- Fixed Bearer authorization in `/api/v1/users/logs` and `/api/v1/users/logs-count-per-status` API calls. remp/remp#865
- Added support for checkbox in the settings page. remp/crm#1624
- Fixed Mailer forms - all fields are required and couldn't be changed. remp/remp#837
- Added UrlParserGenerator requires articles urls to be filled. remp/remp#837
- Fixed incompatible types warning in `ConfigsRepository`. remp/remp#874

## [0.21.1] - 2021-03-30

### [Mailer]

- Fixed "template is not defined" errors during sending if snippets were used. remp/remp#871

## [0.21.0] - 2021-03-29

### [Campaign]

- Fixed adding of new variant to the existing campaign. remp/remp#860

### [Mailer]

- Added support for multiple mailers of the same class. Added optional `code` parameter to `Mailer` constructor to set unique code for mailer and differentiate between mailers of the same type. remp/remp#828
- Added `code` option to `MailgunEventsCommand` to set up command for specific Mailgun mailer. remp/remp#828
- Fixed validation of mail generator API. remp/remp#866 

## [0.20.0] - 2021-03-23

### Project

- **BREAKING**: All applications now require Node 12+. Please schedule update of Node accordingly.
- Updated docker image of Telegraf to the currently latest 1.17.3.
- Fixed occasional very slow initialization of grid filters causing whole grids to load slowly (tens of seconds).

### [Beam]

- Added new function remplib.tracker.setArticle to allow setting article information to REMP JS tracking script after the script was already initialized. remp/crm#1635
- Added new optional parameter `includeStorageParams` for internal function `addSystemUserParams` to allow `trackCheckout` function gets stored tracking parameters. remp/crm#1617
- [Tracker] Fixed possible overwrite of category when tracking pageview and event APIs with `category` key in tracked tags.
- Added statistics and detail page for sections and tags. remp/remp#776
- Fixed remplib initialization flow - there was a window where remplib wasn't yet initialized but public functions would be callable. These calls would cause errors. remp/remp#856

### [Campaign]

- Fixed bug for some banner templates if the saved text was too long. remp/remp#819
- Fixed text warning in campaign form segments list. remp/remp#584
- Added emitting of `remp:showtimeReady` JS event when all banners from showtime were processed. Event can be used when you want to run your implementation after the showtime asynchronous execution. remp/web#1393
- Fixed missing click tracking in OverlayTwoButtonSignature banner preview template. remp/remp#797
- Fixed broken control group tracking when showtime experiment (fast implementation) is used.
- Fixed missing campaign_id in banners created by CampaignSeeder. remp/remp#838

### [Mailer]

- **BREAKING**: Updated underlying Nette framework to the latest version (3.1).
  - Update Composer to version 2.
  - In `app/config/config.local.neon` replace `class` keyword with `factory`.
  - If you implemented your own mailers:
    - Interface `Nette\Mail\IMailer` should be replaced in favor of `Nette\Mail\Mailer`.
  - If you implemented your own authenticators:
    - Interface `Nette\Security\IAuthenticator` should be replaced in favor of `Nette\Security\Authenticator`.
    - Interface `Nette\Http\UserStorage` should be replaced in favor of `Nette\Bridges\SecurityHttp\SessionStorage`.
    - Interface `Nette\Security\Identity` should be replaced in favor of `Nette\Security\SimpleIdentity`.
  - If you implemented your own API handlers:
    - API handler registration method (the one called in the config) changed from `addApiHandler` to `addApi`
    - API handler parameter definition changed. If you implement your own handlers, change the definition based on the following examples:
      ```php
      [
          new InputParam(InputParam::TYPE_POST, 'name', InputParam::REQUIRED),
          new InputParam(InputParam::TYPE_GET, 'mail_type_codes', InputParam::OPTIONAL, null, true),
          new InputParam(InputParam::TYPE_POST_RAW, 'raw'),
      ];
      ```
      
      ```php
      [
          (new PostInputParam('name'))->setRequired(),
          (new GetInputParam('mail_type_codes'))->setMulti(),
          new RawInputParam('raw'),
      ];
      ```
    - API handle `handle` method signature changed to `handle(array $params): ResponseInterface`.
  - Configuration of form control html attributes changed from `setAttribute` to `setHtmlAttribute`.
- Added new `codes` parameter to `GET /api/v1/mailers/templates` API endpoint. Parameter is used to list email templates for given mail_template codes. remp/remp#814
- Added `click_tracking` parameter to `POST /api/v1/mailers/templates` to define whether click tracking should be enabled/disabled/use default for created template. remp/remp#824
- Added *snippets* which are independent part of emails that can be included across all templates, layouts and other snippets. remp/remp#550
- Fixed email conversion matching and processing. Bug was introduced in `0.17.0` and caused no conversions to be matched with the emails. remp/remp#834
- Added support for textarea in the settings page. remp/crm#1624.
- Changed dashboard subscribers count chart readability. Instead of absolute counts the chart now displays gain/loses of subscribers compared to 30 days ago. remp/remp#701
- Changed list detail subscribers count chart readability. Instead of absolute counts the chart now displays gain/loses of subscribers compared to 30 days ago. remp/remp#701
- Added `mail_type` to *snippets* to define snippets with same `code` for different mail types. When rendering mail template that includes snippet, first it tries to match snippet with mail type of given template. If there is not specified snippet with mail type then it uses general snippet with no mail type included. remp/remp#816

## [0.19.0] - 2021-02-16

### Important

We have identified possibly incorrectly stored values in `timestamp` columns of Beam/Campaign/SSO applications.

This issue could have occurred if your database time_zone was not set to the UTC and used any other local timezone. All features worked correctly, but the underlying date was not stored correctly and issues could have emerged later.

If you're not sure if your database is in UTC, follow these steps to verify and fix the issue. Otherwise you could see incorrect dates in the app after the update to this version. 

1. Check whether your database is configured in UTC. Please run following query in Beam:
    ```sql
    select published_at, convert_tz(published_at, '+00:00', @@session.time_zone) from articles order by published_at desc limit 1;
    ```
    The result will look like this:
    ```
    +---------------------+---------------------------------------------------------+
    | published_at        | convert_tz(published_at, '+00:00', @@session.time_zone) |
    +---------------------+---------------------------------------------------------+
    | 2021-01-25 14:01:04 | 2021-01-25 15:01:04                                     |
    ```

2. If the two dates are the same, your DB uses UTC and you're fine. Otherwise, proceed to this tutorial: https://gist.github.com/rootpd/c5e04612e47c80a10635a0477a4afa8e.

### [Beam]

- Added option to filter top articles and top tags by author. remp/remp#803
- [Segments]: Improved live caching of segments, avoiding queries that are not necessary to execute.
- [Segments]: Explicitly closing open Elastic scrolls once we don't need them anymore, since they're expensive to maintain for Elastic.
- Added explicit DB connection time zone to enforce UTC communication between application and database. remp/remp#809
- Added command for processing of conversion sources that runs in batch mode or for specific conversion (`php artisan conversions:process-sources [--conversion_id]`). remp/remp#464
  - Added new `conversion_sources` table. remp/remp#464
  - Processing of conversion sources is also invoked in conversion upsert endpoint right after aggregation of conversion events. remp/remp#464
- Calling of conversion events aggregation has been moved into separate job class. Another job class has been created for conversion sources command as well. remp/remp#464
- Added new columns in `articles.show` view into referer stats section - `first conversion source count` and `last conversion source count`. remp/remp#464
- [Segments]: Added new referer-based fields to the API responses (see Swagger for additional information):
  - `derived_referer_host_with_path`
  - `derived_referer_medium`
  - `derived_referer_source`

### [Campaign]

- Fixed possible issue with campaign stats A/B test evaluation if variant had 100% conversion rate.
- Added explicit DB connection time zone to enforce UTC communication between application and database. remp/remp#809

### [Mailer]

- **BREAKING**: Service `hermesRestart` was renamed to `hermesShutdown` to correctly indicate its behavior.
  - If you used it in your configuration, please replace the usage of `hermesRestart` to `hermesShutdown`.
  - Replace the use of `Tomaj\Hermes\Restart\RedisRestart` to `Tomaj\Hermes\Shutdown\RedisShutdown`.
  - Replace the use of `Tomaj\Hermes\Restart\SharedFileRestart` to `Tomaj\Hermes\Shutdown\SharedFileShutdown`.
- Added support for custom Message-ID headers in Mailer in Mailgun implementation. Mailgun reused same Message-ID for all emails within one batch which could cause unexpected behavior. remp/remp#801
- Fixed type-related issue with Mailgun event daemon processor which caused synchronization not to work.
- Added support for Hermes message priorities. See `app/config/config.local.neon.example` for example use of registering multiple priority queues for Hermes.
- Fixed injection of `UNSUBSCRIBE_URL` environment variable into the email parameters. Sender was injecting only name of the newsletter list instead of the whole `UNSUBSCRIBE_URL` with `%type%` replaced with the actual newsletter list code. remp2020/remp#87
- Added option to configure which mailer to use for each mail type (list) separately. remp/remp#793

### [Sso]

- Models from `\App` namespace moved to `App\Models` (compatibility with current Laravel conventions).
- Added explicit DB connection time zone to enforce UTC communication between application and database. remp/remp#809

## [0.18.0] - 2021-01-14

### [Beam]

- Added caching of colors order in dashboard (order is first assigned according to predefined order and number of traffic per each referer medium). remp/remp#719
- Added missing IE11 polyfill to support `Promise` in IE11. remp/remp#795
- Added support to track IDs of user's subscriptions granting access to content. remp/analytika#11
- Added optional env variable `COMMANDS_OVERLAPPING_EXPIRES_AT` which controls overlaping expiration and changed default commands without overlaping expiration to 15 minutes instead of 24 hours. remp/remp#768

### [Campaign]

- Added *Newsletter rectangle* banner type to allow subscription to newsletter directly from within the banner. Further configuration and backend proxy is necessary to use the feature. Refer to README and `.env.example` for additional info. remp/remp#618
- Added missing IE11 polyfill to support `Promise` in IE11. remp/remp#795
- Added optional env variable `COMMANDS_OVERLAPPING_EXPIRES_AT` which controls overlaping expiration and changed default commands without overlaping expiration to 15 minutes instead of 24 hours. remp/remp#768
- Moved caching of `SegmentAggregator` (required in `showtime.php`) to HTTP middleware and `CampaignsRefreshCache` command. Done to avoid caching when running `composer install` and its hooks (such as `artisan package:discover`). remp/remp#798

### [Sso]

- Added optional env variable `COMMANDS_OVERLAPPING_EXPIRES_AT` which controls overlaping expiration and changed default commands without overlaping expiration to 15 minutes instead of 24 hours. remp/remp#768

### [Mailer]

- Fixed issue with Twitter embeds caused by the external library. remp/remp#796

### [Mailer]

- Added option to show mail template by code in `TemplatePresenter->showByCode` method. remp/crm#1626

## [0.17.0] - 2020-12-21

### Important

- **BREAKING**: All REMP applications now primarily use *rtm* parameters (our replacement for *utm* parameters, to avoid conflicts with other tracking software).
When deploying this release, **you have to deploy the Segments/Journal app first and the Tracker app second**. Everything else (Beam, Campaign, Mailer, SSO) **has to be deployed afterwards** (the order doesn't matter). This is due to the internal change in how Tracker stores *rtm* parameters in underlying Elasticsearch storage. 
- **BREAKING**: Segments/Journal app aggregations using *utm* parameters (namely `utm_campaign`, `utm_content`, `utm_medium`, `utm_source` and `banner_variant`) has to be rewritten to use *rtm* parameters (`rtm_campaign`, `rtm_content`, `rtm_medium`, `rtm_source` and `rtm_variant`). If one wants to aggregate old data (with `utm_` parameters) together with new data, it has to be done in two separate calls to Segments API (one with `utm_` and another one with `rtm_` aggregation parameters).

### Project

- Fixed PHP 7.4 docker image build dependencies.

### [Mailer]

- Fixed issues with settings page caused by internal config names renaming. remp/crm#1616
- Fixed main search issue due to the internal changes in the past release. remp/remp#786
- Improved search speed of the main search bar. remp/remp#786
- Fixed bug on job edit form causing error when loading the form.
- Fixed conversion stats processing if there are no templates/batches to process.
- Fixed rendering issues of 4xx pages when invalid page was requested.
- Fixed conversion stats processing type-related issues.
- Fixed `mail_layout_id` type-related issues in `/api/v1/mailers/templates` API.

### [Beam]

- **BREAKING**: API endpoints to get "top" tags or authors now utilize their `external_id` to filter the data. Please make sure you already use `/api/v2/articles/upsert` to populate article information.
- Fixed scenario in JS library when fallback `cookie` value expiration was not updated with the main `local_storage` expiration.
- [Tracker]: Added new parameter `commerce_session_id` to `track/commerce` endpoint of API tracker. remp/crm#1559
- Added new identifier `commerce_session_id` into `remplib.js` to identify unique commerce process. remp/crm#1559 
- [Segments]: UTM to RTM parameters transition. remp/remp#779
- [Tracker]: UTM to RTM parameters transition. remp/remp#779
- Removed statically set `memory_limit` configuration within some memory-extensive commands. remp/remp#788
- Added support for configurable memory limit for each command via `COMMANDS_MEMORY_LIMITS` environment variable. See [`.env.example`](Beam/.env.example) for more information. remp/remp#788
- Improved dashboard graph - snapshots of concurrent data are mapped to fixed time points to avoid displaying glitches in graph. remp/remp#763
- Changed scheduled aggregation and snapshotting command calls to be non-blocking (so they don't wait for each other to be executed). remp/remp#763
- Command `composer install` now works without DB connection (removed check for `migrations` table)

### [Campaign]
- Command `composer install` now works without DB connection (removed check for `migrations` table)

## [0.16.0] - 2020-12-07

### Project

- Removed `--no-bin-links` switch from `Docker/php/remp.sh installation script. Bin symlinks are required after the latest changes in Yarn package commands.
- Added PHP 7.4 syntax check to `.gitlab-ci.yml`.

### [Beam]

- Added caching to Tracker preflight requests to limit number of OPTIONS calls. Cache is now set to 1 hour (3600 seconds) and it effectively adds `Access-Control-Max-Age: 3600` header to preflight responses.
- Fixed issue with slow pageview processing queries due to string/int type conflict in the query parameters. remp/remp#766
- [Segments]: Fixed `search.max_buckets` Elastic issue when aggregating too big chunk of pageviews data. Changed internal implementation of Segments API, aggregation is now using Elastic composite aggregation and pagination. remp/remp#662 
- [Segments]: Fixed return type of count histogram items. Float was changed to int as count can always return integers anyway. remp/remp#622
- [Segments]: Removed `offset` parameter for histograms. Beam APIs haven't used it and composite index (new Elastic-7-friendly implementation) doesn't support it yet. remp/remp#622
- Added filter by content type to conversion, article pageviews, article conversions and author detail listing. remp/remp#769
- Added ability to compute section stats segments using `ComputeSectionSegments` command + added configuration category and items for this feature + added test configuration screen which uses command to generate results and sends them to specified email address. remp/remp#424
- Added run `ComputeSectionSegments` command to laravel console Kernel. remp/remp#424

### [Campaign]

- Allowed search and paging in dashboard schedules table. remp/remp#755
- Fixed possibility of banner close buttons being overlayed by Safari scrollbar. remp/remp#764
- Extended banner close button area for easier manipulation on touch-based devices. remp/remp#764
- Fixed initialization of REMP JS library (may have caused a bug when banner aimed to anonymous users was shown to a logged-in user). remp/crm#651
- Fixed invalid logger access in Showtime request causing fatal errors if user's adblock information was missing. remp/remp#774

### [Mailer]

- **BREAKING**: Some portion of classes was not PSR-0/PSR-4 compliant and were moved to their correct folders/namespaces. Please update your `config.local.neon` based on the updated example file. 
- **BREAKING**: Updated [league/event](https://event.thephpleague.com/) from [version 2 to 3](https://event.thephpleague.com/3.0/upgrade-from-2-to-3/) - there is config change - please change `League\Event\Emitter` to `League\Event\EventDispatcher`
- **BREAKING**: Removed `Remp\MailerModule\Console\Application` and keep only native Symfony Application. Change in config -> use `add` instead of `register`
- **BREAKING**: Updated Twig library to version 3. Also introduced IEngine for templates rendering. More detail [here](https://symfony.com/blog/preparing-your-applications-for-twig-3)
- **BREAKING**: Replaced `Remp\MailerModule\Replace\ReplaceInterface` with `Remp\MailerModule\ContentGenerator\GeneratorInput\IReplace`. Please see the example implementations and update your implementation accordingly.
- **BREAKING**: Added php types into most missing places (interfaces change). Please run `make phpstan` after update to check your custom changes.
- **BREAKING**: Unified usage of ActiveRow, IRow and DateTime (interfaces change). Please run `make phpstan` after update to check your custom changes.
- Added `declare(strict_types=1);` to all php files.
- Fixed possible issue with asset location on Mac when Valet is used for development.
- Upgraded vlucas/phpdotenv from version 2 to 5 and removed usage of `getenv()` function.
- Removed obsolete deploy script.
- Fixed Hermes worker `RedisDriver` not restarting if there are no new tasks to handle. remp/crm#1561
- Refactored bootstrap file to follow new Nette skeleton structure.
- Added missing `user_id` index to `mail_user_subscriptions` table.
- Updated monolog/monolog from version 1 to 2
- Updated mailgun/mailgun-php from version 2 to 3
- Updated robmorgan/phinx to 0.12.
- Updated phpunit/phpunit from version 7 to 9.
- Fixed incorrect _unsubscribed_ stats for emails sent directly to users (not via jobs). remp/remp#771
- Fixed crash in `mail:process-job` command when article meta fetch fails. remp/remp#773  
- Added graph of unsubscribed users in time to mail type detail. remp/remp#631

## [0.15.0] - 2020-10-15

### Project

- **BREAKING**: Bumping minimal version of PHP to 7.3. Version is enforced by Composer and any older version is not allowed anymore. Please upgrade PHP accordingly.
- Docker image now uses PHP 7.3.
- Go images now use 1.15.

### [Beam]

- Updated major portion of dependencies. Laravel was not updated yet.
- Added parameter `article_id` to `AggregatePageviewLoadJob` and `AggregatePageviewTimespentJob` commands.
- Fixed broken functionality of the segments flag `is_article` (available in pageview category). remp/remp#716
- Added health check _(http://beam.remp.press/health)_ for database, Redis, storage and logging. remp/remp#735
- Added "Content Type" to article detail information.
- Fixed reseting `paid_at` column in `conversions` table on each row update. remp/remp#738

### [Campaign]

- Fixed listing of banners over API (`/api/banners`).
- Added health check _(http://campaign.remp.press/health)_ for database, Redis, storage and logging. remp/remp#735

### [Mailer]

- **BREAKING**: Changed hermes restart implementation (handles restarts of hermes worker and mail worker). Please update your deploy process to set the deploy time to Redis or fallback to previous file-based restart (see `config.local.neon.example` for reference). remp/remp#736
  - Updated `tomaj/hermes` to version 3.0.0 which introduced `RedisRestart` implementation.
  - Switched default Fermes restart setting from file restart to redis restart.
  - File restart moved to `config.local.neon.example` as example configuration.
- Added parameter `with_mail_types` for `GET /api/v1/mailers/templates` endpoint, allowing to add details about mail_types assigned to templates. Documentation for the endpoint added. remp/crm#1450
- Added method to use Redis `ping()` command within MailCache. remp/remp#735
- Added health check _(http://mailer.remp.press/health)_ for database, Redis, storage and logging. remp/remp#735
- Added graceful shutdown to `MailWorkerCommand` (`worker:mail`) via `Tomaj\Hermes\Restart\RestartInterface`. remp/remp#736
- Add missing indexes to mail_logs table. remp/remp#750

### [Sso]

- Updated major portion of dependencies. Laravel was not updated yet.
- Added health check _(http://sso.remp.press/health)_ for database, Redis, storage and logging. remp/remp#735

## [0.14.0] - 2020-09-29

### Project

- PHP CodeSniffer scripts (`phpcbf`, `phpcs`) updated to version 3.5.6 (now supporting PHP 7.3+).
- Added Gitlab CI `test` stage dependency on `redis:3.2` version (tests now use real Redis instance).

### Docker

- Fixed Elasticsearch index initialization for new installations.

### [Beam]

- Added prevention of overlapping run of SnapshotArticlesViews command, which may have caused incorrect numbers in Beam dashboard concurrents graph.
- Added `browser_id` to Commerce model to expose it in commerce-related responses of Segments API.
- Most read articles endpoint in `DashboardController` now returns data for article pageviews sparkline chart as well. remp/remp#540
- Added new interval option (1day) into `JournalInterval` helper class. remp/remp#540
- Pageview sparkline chart data are being retrieved from journal(default) or snapshots, based on `PAGEVIEW_GRAPH_DATA_SOURCE` env variable. remp/remp#540
- Added new column for pageview charts into dashboard articles overview table. remp/remp#540

### [Campaign]

- Fixed change of missing campaign statistics caused by invalid pairing of data with labels due to inconsistent timezone use.
- Fixed possibility of zero campaign stats. Bug appeared if campaign included banner with an already removed variant with some stats tracked. remp/remp#628 
- Fixed "how often to display" campaign rules, previously broken due to expiration of counter data in local storage. remp/remp#715 
- Fixed possibility of zero campaign stats. Bug appeared if campaign included banner with an already removed variant with some stats tracked. remp/remp#628
- Removed redundant Tracker contract and related implementations. It was never used and necessary. Campaign should only consume Journal data, not produce them from backend. 
- Increased campaigns backend stats fetch timeout to 5 seconds.

### [Mailer]

- Fixed README.md typos, incorrectly linked classes, wording changes, small grammar fixes. remp/remp!390
- Upgraded nette/application to 2.4.16.
- Added support for click tracking configuration on mail template level. dn-mofa#50

### [Sso]

- Added initial support for multiple providers. No real providers were actually added yet. remp2020/remp#87

## [0.13.0] - 2020-09-03

### [Beam]

- **BREAKING**: Application now requires Elasticsearch 7. remp/remp#616
Please follow the upgrade steps:
  - Rebuild or download new Tracker and Segments binaries (binaries available at https://github.com/remp2020/remp/releases).
  - Omit `type_name` from Telegraf configuration (see `Docker/telegraf/telegraf.conf` docker configuration file for more details).
  - If you use default docker appliance to run REMP, please run:
    ```bash
    docker-compose stop beam_tracker beam_segments elasticsearch
    docker-compose build beam_tracker beam_segments elasticsearch
    docker-compose up -d beam_tracker beam_segments elasticsearch
    ```
- Go dep dependencies management system replaced with go modules. remp/remp#616
- Added ability to optionally specify (Elasticsearch) indexes prefix in `.env` for Tracker and Segments apps. remp/remp#616
- Added support for Elasticsearch authentication (`auth` parameter) in `ElasticDataRetention` and `ElasticWriteAliasRollover` commands. remp/remp#616
- Added `content_type` column to `articles` table. remp/remp#695
- Added optional parameter `content_type` in `/api/v2/articles/upsert` API endpoint. remp/remp#695
- Added optional parameter `content_type` in `/api/articles/top` API endpoint to filter articles by `content_type`. remp/remp#695
- Added optional parameter `content_type` in `/api/authors/top` API endpoint to filter articles by `content_type`. remp/remp#695
- Added optional parameter `content_type` in `/api/tags/top` API endpoint to filter articles by `content_type`. remp/remp#695

### [Campaign]

- Fixed store pageview counts for campaign separately instead of globally. remp/remp#609
- Refactor campaign form pageview rules to use only `every` rule. remp/remp#609
- Added display N times and then stop rule to campaign form banner rules. remp/remp#609
- **BREAKING** Added migration to convert old campaign pageview rules to new format. May pause campaigns with not convertable rules. remp/remp#609
- Added new banner type – Overlay with Two Buttons and Signature. remp/remp#650

## [0.12.0] - 2020-08-11

### Docker

- Added tzdata installation for remp_segments docker (required by golang).

### [Beam]

- **BREAKING**: Changed way of specifying `sections` parameter in `/api/articles/top` API endpoint. Now sections can be filtered either using `name` or `external_id` parameters (before, `name` parameter was used implicitely). remp/remp#691
- Added ability to specify `sections` parameter in `/api/authors/top` and `/api/tags/top/` API endpoints via `name` or `external_id` parameters (in the same fashion as in case of `/api/articles/top`). remp/remp#691
- Pageviews data for articles are now refreshed every minute instead of every hour. remp/remp#663
- Fixed ignored explicit `browserId` parameter in JS configuration. remp/remp#690
- Commands `pageviews:aggregate-load` and `pageviews:aggregate-timespent` do not show progress unless `--debug` parameter is specified.
- [Segments]: Fixed possibility of missing aggregations if Elastic was not able to resolve values for a sub aggregation because there were no records within the sub-aggregation branch.
- Fixed `remplib.js` generating `undefined` cookies when JS is run on a page with no query parameters. remp2020/remp#81
- **BREAKING:** Field `locked` removed from `configs` table. remp/remp#494
- **BREAKING:** Field `config_category_id` in `configs` table is mandatory and cannot be null anymore. remp/remp#494
- Added new config category `Author Segments` and paired respective configs with this new category. remp/remp#494
- Changed settings screen to contain configs grouped by categories in separate linkable tabs. remp/remp#494
- Added configure button in `more options` modal that navigates to dashboard section of settings remp/remp#494
- Removed components that are not used anymore, namely: form for author-segments config, methods `saveConfiguration` and `validateConfiguration` of `AuthorSegmentsController` remp/remp#494
- Removed configs for author segments from config page that was accessible from authors segments page config button, only testing configs remained remp/remp#494
- Added `more options` dropdown in Author segments index page, dropdown contains redirects to author segments config tab in settings page and to testing configuration (former authors segments config page) remp/remp#494
- Added/ported validation of author segments configs to respective settings tab, code is prepared to handle validations for other config categories as well, see `SettingsController::update()` remp/remp#494
- Added button into author segments config tab for testing of author segments config (redirects into testing page). remp/remp#494

### [Mailer]

- Added handling for `UserNotFoundException` when confirming user in CRM. remp/remp#685
- Added notification on the settings screen about settings overridden by local config file. remp/remp#519

## [0.11.1] - 2020-07-10

### [Beam]

- Added environment variables to configure Redis databases in Laravel. remp/remp#671 

### [Campaign]

- Added environment variables to configure Redis databases in Laravel. remp/remp#671

### [Sso]

- Added environment variables to configure Redis databases in Laravel. remp/remp#671

## [0.11.0] - 2020-06-30

### Important

**Elasticsearch upgrade notice.** We'll be raising Elasticsearch compatibility to 7.* in the next release. Up until now, Segments API supported Elasticsearch 6.*.

We recommend two upgrade scenarios:

- When new release is ready, you can upgrade your existing cluster based on the documentation available at https://www.elastic.co/guide/en/elasticsearch/reference/current/setup-upgrade.html.
- If you clear your Elastic data recurrently and archive stored events to CSV, you can spin up new v7 cluster and configure [Telegraf](./Docker/telegraf/telegraf.conf) to push data to both v6 and v7 of Elastic. Once you're satisfied with the amount of data in v7 (~1 month tends to be sufficient), wait for the next release and change Elastic address in Segments API to v7 cluster. If there are no issues, you can stop pushing new events to v6 cluster in Telegraf and stop the cluster completely. 

### Docker

- **BREAKING**: Replaced `spotify/kafka` docker image with `wurstmeister/kafka` as original image was no longer maintained and new installations stopped working. remp/remp#638
  
  - In case you have existing installation in place using the docker compose, please run:
    ```bash
    docker-compose stop beam_tracker telegraf
    docker-compose rm beam_tracker telegraf
    docker-compose build beam_tracker telegraf
    ```  

### [Mailer]

- Added API endpoint `mailers/mail-type-categories` to list mail type categories. GH-64
- Extended API endpoint `mailers/mail-types` to include additional data and filter via `public_listing` and `code` parameters. GH-64
- Added API endpoint `users/is-unsubscribed` to check if user is explicitly unsubscribed from the newsletter. GH-64
- Added API endpoint `users/logs-count-per-status` to get number of emails sent to user per each status within provided timeframe. GH-64
- Added API endpoint `users/logs` to retrieve logs of emails sent to user. Various filter can apply, see [README.md](./Mailer/README.md) for more details. GH-64
- Added JSON schema validation to `Subscribe` and `BulkSubscribe` APIs. GH-64
- Added API endpoint `users/preferences` to read user's subscriptions to newsletters (mail types). GH-64
- Improved speed of job detail page - unsubscribe stats could slow rendering a bit if job was sent to 6+-figure recipients. remp/remp#624
- Added API endpoint `mailers/mail-templates` to list and filter available mail templates.
- Added early-version support for search in the top searchbox. Searchable are emails (templates), layouts, jobs and newsletter lists. GH-69
- Added early-version support for WYSIWYG editor in Template edit form. It's configurable in `config.local.neon`, Mailer keeps HTML editor as default for now. GH-58

### [Beam]

- Added early-version support for search in the top searchbox. Searchable are articles, authors, sections, tags and segments. GH-62
- Added support for timezone parameter in Journal aggregations. remp/remp#605
- Quick range day filters now start from beginning of the day. remp/remp#605
- Added `FORCE_HTTPS` environment variable to enforce HTTPS generation to URLs instead of determining protocol based on the request. This is useful in case you're running your application on `https`, but internally use proxy forwarding the request via `http`. remp/remp#619
- Added new APIs `api/authors/top` and `api/tags/top` for retrieving top authors and tags per given time period. remp/web#366
- Articles upsert v2 api endpoint - process article titles only if they are present in payload. remp/remp#646
- Fixed remplib initialization which could use misidentification of user - `remplib.getUserId()` would return `null` even when the `userId` was correctly set in `rempConfig`. remp/remp#651

### [Campaign]

- Added early-version support for search in the top searchbox. Searchable are banners and campaigns. GH-62
- Improved intervals in campaign stats charts. remp/remp#605
- Quick range day filters now start from beginning of the day. remp/remp#605
- Added `FORCE_HTTPS` environment variable to enforce HTTPS generation to URLs instead of determining protocol based on the request. This is useful in case you're running your application on `https`, but internally use proxy forwarding the request via `http`. remp/remp#619
- Fixed add new ab variant replaces last variant instead of adding new after last one. remp/remp#634
- Added option to disable banner events tracking. remp/remp#636
- Added ability to access banner properties in custom JS code run in banner via newly added `params` object. remp/remp#636
- Changed wording of hints in campaign's segment selection form. remp/remp#645
- Fixed remplib initialization which could use misidentification of user - `remplib.getUserId()` would return `null` even when the `userId` was correctly set in `rempConfig`. remp/remp#651

### [Sso]
- Added `FORCE_HTTPS` environment variable to enforce HTTPS generation to URLs instead of determining protocol based on the request. This is useful in case you're running your application on `https`, but internally use proxy forwarding the request via `http`. remp/remp#619

---

## [0.10.0] - 2020-04-07

- Datatables in all projects now store filters/ordering in URL hash, not in local storage. Previous solution was buggy and didn't allow users to share their filters with other users. GH-56
- Javascript building now supports linking of shared `remp` package within the projects. You can now link the package with `yarn link --cwd ../Package/remp && yarn link remp` inside the app folder. Command `yarn watch` is able to watch for changes in the shared package. GH-63

### [Beam]

- Added support for conversion rate sorting in Conversions data table. remp/remp#306
- Fixed error thrown when using main search on authors listing. remp/remp#531
- Added command to maintain data retention for rolled-over Elastic indices. remp/remp#527
- Pageviews graph in article details page loads data from Journal snapshots instead of directly quering Journal API by default. Option `PAGEVIEWS_DATA_SOURCE` added to `.env` file to allow switching to old behaviour. remp/remp#442
- `rempConfig.tracker` configuration option `explicit_referer_medium` is deprecated and renamed to `refererMedium`. The old one is still accepted due to compatibility reasons.
- Added support for referer medium renaming - one can specify label for each medium by adding a record to `referer_medium_labels` table. remp/remp#543
- Updated layout footer with link to REMP website. remp/remp#522 HEAD
- Added ability to display external events in article detail and dashboard. remp/remp#574
- Added (optional) `article_id` field to event parameters pushed to tracker. remp/remp#556
- Fixed property naming validator issue checking unique constraint against account names, not property names.
- Added article conversion/pageviews stats filtering based on selected property. GH-50
- Added support for article tags (storing tags associated with articles, filtering in data tables). remp/remp#217
- Added top articles endpoint for listing top articles by time, sections. remp/web#1010
- Fixed duplicate conversions if multiple sections/tags/authors were linked to article.
- Fixed missing conversions if no section/tag/author was linked to article. remp/remp#586
- Added articles upsert v2 api endpoint because of added `external_id` to `tags`, `authors` and `sections` tables. remp/remp#599

### [Campaign]

- Updated layout footer with link to REMP website. remp/remp#522
- Fixed missing validation rules for collapsible bar banner template. remp/remp#558
- Added support for campaign segment exclusion. User now need to match both inclusion and exclusion segment rules to see the campaign banner. GH-33
- Overal rectangle banner button text and main text attributes are now optional. This is useful when e.g. only using picture inside the banner. remp/remp#582
- Texts in collapsible bar, bar and medium rectangle bar templates are now optional. remp/remp#597

### [Mailer]

- **BREAKING**: Attachment parameter in `send-email` API is now required to be base64-encoded to support PDF (and other binary) attachments.
- **BREAKING**: Context checking during email sending now only checks if user received an email with given context before; Mailer ignores `mail_template_code` being sent. In previous version, two different mail templates with same context could be sent to the same user. This version prevents such behavior. remp/crm#987
- **BREAKING**: Removed hardcoded support for Errbit/Airbrake error logging, added support for Sentry logging. See README for details on how to configure Sentry to track errors.
- Updated layout footer with link to REMP website. remp/remp#522
- Added possibility to filter hermes payload parameters in logs. Parameters `password` and `token` are already filtered by default. See `config.neon` for reference how to extend filtering with own parameters.
- Added hermes handler to unsubscribe users from mail type if emails are dropped. You can enable the feature in `config.local.neon` (see example file for reference). remp/remp#566
- Added configuration for allowed preflight (`OPTIONS`) headers into configuration. You can configure them via `%preflight.headers` parameter - see [example use](Mailer/app/config/config.neon).
- Fixed possible duplicate email for same context being sent, in case the emails were scheduled at the same time via `send-email` API.
- Fixed and redesigned mailing list variants statistics to be sortable and filterable. remp/remp#593
- Fixed attachment_size column type - changing to integer (from incorrectly assigned datetime).
- Added option to select events to handle when starting Hermes worker in case there's need to run separate workers for mission-critical event types.
- Fixed possibly incorrectly skipped newsletter subscription in user registered API handler. remp/crm#1159

### [Sso]

- Updated layout footer with link to REMP website. remp/remp#522

---

## [0.9.1]

### [Beam]

- Numeric columns in conversion are sorted descending first (and only way). remp/remp#306
- Fixed occasional duplicate records in article listings across Beam. remp/remp#482
- Fixed missing articles on author detail if the article doesn't belong to any section. 

### [Campaign]

- Added support for one-time banners. remp/remp#512
- Unified z-index of banner in all templates to 100000. remp/web#968

### [Mailer]

- Fixed possible error on newsletter list editing if custom sorting was used. remp/remp#516
- Fixed autologin parameter in unsubscribe email links. remp/remp#518

---

## [0.9.0] - 2019-10-04

> **Elasticsearch upgrade notice**. We'll be raising Elasticsearch compatibility to 7.* in the beginning of 2020 to keep with the latest changes. Current implementation Segments API is tested and maintained again Elasticsearch 6.*. Please plan your upgrade accordingly.

> **Go** was updated to version **1.13**. 

### [Beam]

- Major refactoring and redesign of IOTA (on-site stats) which now include more relevant statistics. GH-24
- Article detail now shows referer (traffic source) statistics. remp/remp#445
- Added command for compressing snapshot data. remp/remp#442
- Timespent interval configuration in JS is now configurable. remp/remp#461
- Added configuration for various views of conversion rate (decimals, multiplier). remp/remp#475
- Added support for property token selection on Beam dashboard. remp/remp#473

### [Campaign]

- Bugfixed notice in showtime when adblock was not detectable. remp/remp#447
- Campaign listing now displays segment names instead of segment codes.
- Bugfixed campaign form not being able to update some options to their default value.
- Added HTML overlay banner template. remp/remp#457
- Added support to pass custom query parameters to displayed banner URL. 
- All links in banner (not just CTA) now include Campaign's UTM parameters. remp/remp#455

### [Mailer]

- Updated Hermes library, logging only error events to the database.
- Refactored UTM parameter replacing in email links.
- Added support to send single emails via API.

## [0.8.2] - 2019-07-11

_Note: generated binaries are the same as in 0.8.1, no need to deploy them if you have 0.8.1 deployed._

> **Elasticsearch upgrade notice**. We'll be raising Elasticsearch compatibility to 7.* in the beginning of 2020 to keep with the latest changes. Current implementation Segments API is tested and maintained again Elasticsearch 6.*. Please plan your upgrade accordingly.

### [Beam]

- (GO) Removed unused vendor files.

### [Campaign]

- Tables `campaign_banners` and `campaign_country` didn't have primary keys since creation. This was preventing migrations on the replicated instances. This release fixes the failing migration issue. https://github.com/remp2020/remp/commit/f211d2c5d40fa3cb5a232396210543e397c6c439

### [Mailer]

- Exit ProcessJobStatsCommand if there is nothing to do.
- Removed obsolete command `ProcessTemplateStatsCommand`. Use `AggregateMailTemplateStatsCommand`.
- Fixing error screen in case SSO wasn't able to log user in https://github.com/remp2020/remp/commit/7fd415459f012c29c991ae763d74c1f0a49fe2cd
  
  Please change following line in your `.env` file in Mailer:
  
  ```
  SSO_ERROR_URL=http://mailer.remp.press/sign/error
  ```


## [0.8.1] - 2019-07-10

> **Elasticsearch upgrade notice**. We'll be raising Elasticsearch compatibility to 7.* in the beginning of 2020 to keep with the latest changes. Current implementation Segments API is tested and maintained again Elasticsearch 6.*. Please plan your upgrade accordingly.


### [Beam]

- Added experimental database-based dashboard data source which works with snapshot of "concurrents" data made every minute. Snapshotting is being done automatically for you against *concurrents_by_browser* elastic index. This should be populated by your Telegraf instance based on [similar configuration](https://github.com/remp2020/remp/blob/master/Docker/telegraf/telegraf.conf#L73).

- [Segments]: Fixing mapping fetching changes which was causing "include_type_name" deprecation notices since Elastic 6.8. This is to prepare to provide full compatibility for Elastic 7.* and drop compatibility promise for 6.* in the following months.


## [0.8.0] - 2019-07-08

_Note: Generated binaries were not changed since 0.7.0, there's no need to redeploy them if you have 0.7.0 deployed._

### [Beam]

- New functions for tracking commerce events were added to give caller more precise control over what is being tracked.  See documentation of following methods in https://github.com/remp2020/remp/tree/master/Beam#js-tracking-interface:

  - `remplib.tracker.trackCheckoutWithSource`
  - `remplib.tracker.trackPaymentWithSource`
  - `remplib.tracker.trackPurchaseWithSource`
  - `remplib.tracker.trackRefundWithSource`

- In some cases, Beam's database could have been migrated with invalid default value for conversion's _paid_at_ column. https://github.com/remp2020/remp/commit/553c2dcf9120b0b54e938d7e3f5f1a9dca3a73e2

### [Campaign]

- New configuration options were added to append extra query parameters to banner's target URL in case target page needs to identify source page/article and referer is not enough.

  See `rempConfig.campaign.bannerUrlParams` object at https://github.com/remp2020/remp/tree/master/Campaign#javascript-snippet for more detailed info:

  ```
  bannerUrlParams:  {
      "foo": function() { return "bar" },
      "baz": function() { return "XXX" }
  }
  ```

- HTML banner now supports custom JS and CSS inclusion from external sources.
- Bugfixing HTML banner's Farbtastic color picker which was not being initialized after the latest dependency changes.
- Material checkbox was not being rendered properly in Mailer causing custom generator implementations not being able to use it.

### [Mailer]

- If generated queue was too big, memory exhausted exception was possible. Pagination was added to queue generation prevent the issue.


---

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

[Unreleased]: https://github.com/remp2020/remp/compare/0.23.0...master
[0.23.0]: https://github.com/remp2020/remp/compare/0.22.0...0.23.0
[0.22.0]: https://github.com/remp2020/remp/compare/0.21.5...0.22.0
[0.21.5]: https://github.com/remp2020/remp/compare/0.21.4...0.21.5
[0.21.4]: https://github.com/remp2020/remp/compare/0.21.3...0.21.4
[0.21.3]: https://github.com/remp2020/remp/compare/0.21.2...0.21.3
[0.21.2]: https://github.com/remp2020/remp/compare/0.21.1...0.21.2
[0.21.1]: https://github.com/remp2020/remp/compare/0.21.0...0.21.1
[0.21.0]: https://github.com/remp2020/remp/compare/0.20.0...0.21.0
[0.20.0]: https://github.com/remp2020/remp/compare/0.19.0...0.20.0
[0.19.0]: https://github.com/remp2020/remp/compare/0.18.0...0.19.0
[0.18.0]: https://github.com/remp2020/remp/compare/0.17.0...0.18.0
[0.17.0]: https://github.com/remp2020/remp/compare/0.16.0...0.17.0
[0.16.0]: https://github.com/remp2020/remp/compare/0.15.0...0.16.0
[0.15.0]: https://github.com/remp2020/remp/compare/0.14.0...0.15.0
[0.14.0]: https://github.com/remp2020/remp/compare/0.13.0...0.14.0
[0.13.0]: https://github.com/remp2020/remp/compare/0.12.0...0.13.0
[0.12.0]: https://github.com/remp2020/remp/compare/0.11.1...0.12.0
[0.11.1]: https://github.com/remp2020/remp/compare/0.10.0...0.11.1
[0.10.0]: https://github.com/remp2020/remp/compare/0.9.1...0.10.0
[0.9.0]: https://github.com/remp2020/remp/compare/0.8.0...0.9.1
[0.8.0]: https://github.com/remp2020/remp/compare/0.7.0...0.8.0
[0.8.1]: https://github.com/remp2020/remp/compare/0.8.0...0.8.1
[0.8.2]: https://github.com/remp2020/remp/compare/0.8.1...0.8.2
[0.9.0]: https://github.com/remp2020/remp/compare/0.8.2...0.9.0
[0.9.1]: https://github.com/remp2020/remp/compare/0.9.0...0.9.1
[Unreleased]: https://github.com/remp2020/remp/compare/0.10.0...master
