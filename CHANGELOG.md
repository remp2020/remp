# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### Project

- **BREAKING**: Raised minimal version of PHP to v8.1. remp/remp#2091
- **BREAKING**: Raised minimal version of Node.js to v18. Older versions are already after its end-of-life and not supported anymore, `v16` ends its life in couple of months. remp/remp#2091
- **IMPORTANT**: Updated configuration of Docker Compose to use non-root users. remp/remp#2091
  - To make sure you use the same user/group within the docker images as in the host machine, follow these steps:
    1. Find out what is the `UID` and `GID` of your user:
       ```
       id -u # UID
       id -g # GID
       whoami # UNAME
       ```
         
    2. Create new `.env` file in the root of `remp` project (based on the `.env.example`):
       ```
       UID=1000
       GID=1000
       UNAME=docker
       ```

    3. Transfer owner of generated files created by previous version of image (owned by `root` user) to user who will use them from now on:
       ```
       sudo chown -R 1000:1000 Beam Campaign Mailer Package Sso
       ```
       If you changed the default `UID` / `GID` to something different, use that in the `chown` command.

    4. Rebuild the docker images, clear caches, and start them again:
       ```
       docker compose stop
       docker compose build beam sso campaign mailer
       docker-compose up -d
       ```

### [Beam]

- Fixed possible performance issues if bigger amount of aggregation data need to be compressed. remp/remp#1246

### [Mailer]

- **BREAKING**: Added support for external module routes (`/<module>/<presenter>/<action>`). remp/remp#1220
  - This new route map changed the default routes and breaks anything linking to the Mailer directly; primarily bookmarks. APIs are not affected by this change. 
- **DEPRECATED**: Deprecated method `LogsRepository::filterAlreadySent` in favor of `LogsRepository::filterAlreadySentV2`. remp/remp#1242
- Removed `php-amqplib/php-amqplib` from the direct Mailer dependencies. remp/remp#1244
- **IMPORTANT**: Changed primary key from `int` to `bigint` for `autologin_tokens` table. remp/remp#1187
  - This migration is a two-step process that requires your manual action - running `mail:migrate-autologin-tokens` in the off-peak hours. Since some tables are very exposed and cannot be locked for more than a couple of seconds, we decided to migrate the data into the new table manually and keep the old and new table in sync. Based on the amount of your data, the migration can anywhere from couple of minutes to hours.
  - Check `Database tables migration` section in `mailer-module` README file for more information.
- Changed `<p>` tag formatting in generators. remp#remp1215 
  - Generators used to remove `<p>` tags from input to then create new `<p>` tags and then add desired styling.
  - Now `<p>` tags are not removed but just changed to desired styling.
- Fixed possible performance issue when sending emails. remp/remp#1242
  - The check executed in the `mail:worker` command didn't perform well under certain DB settings and caused unnecessary hold-ups.
- Fixed `worker:mail` healthcheck not correctly working if worker was occupied with big batch. remp/remp#1240
- Added support for include and exclude segments in mail jobs. Now you can select multiple include and exclude segments for mail job. remp/remp#1216
- Added log `user_id` to `mail_logs` in mail Sender. remp/remp#1188
- Fixed `CreateNewMailLogsAndMailConversionsTable` migration to add `user_id` column and index if database table is empty. remp/remp#1188

## [1.2.0] - 2023-02-23

### [Mailer]

- **BREAKING**: Added explicit types to `RedisClientTrait`.
  - If you use the trait in your own extensions, you might encounter type incompatibility issues of your constructors and class properties. Make the necessary changes based on the error messages.
- **IMPORTANT**: Changed primary key from `int` to `bigint` for `mail_user_subscriptions` table. remp/remp#1187
  - This migration is a two-step process that requires your manual action - running `mail:migrate-user-subscriptions-and-variants` in the off-peak hours. Since some tables are very exposed and cannot be locked for more than a couple of seconds, we decided to migrate the data into the new table manually and keep the old and new table in sync. Based on the amount of your data, the migration can take hours.
- Added the soft delete of mail type variants. remp/crm#2721
- Added ability to log apple bots use in Mailgun "opened" events via standalone Hermes handler (disabled by default). remp/analytika#137
- Added `TrackSubscribeUnsubscribeHandler` hermes handler, which sends event to Tracker after user subscribes/unsubscribes from mail type. remp/remp#1226
- Added ability to track RTM parameters in the `/api/v1/users/subscribe` API. remp/remp#1237
- Added support for standalone HTTP webhook signing key. remp/remp#1232
  - Mailgun used to use domains API key to sign the requests, however it currently is a separate signing key.
- Added filter by `user_id` support to mailer `LogsHandler`. remp/remp#1188

### [Campaign]

- Fixed increasing pageviews for campaigns which banners were not displayed due to the priority rules for banners on the same position. remp/remp#1213
- Added syntax highlighting to Variables section. remp/remp#1214
- Added support for new banner rules in campaign. Now you can determine if campaign should display after user clicked or closed the banner. remp/remp#960
- Added storing of collapse state for collapsible banner. remp/remp#960
  - If user collapses campaign banner then it displays collapsed on the next display. 
  - In collapsible banner settings there is a new toggle to override this behaviour and display banner always in initial state.

## [1.1.0] - 2023-01-27

### Project

- Changed the way how PHP CodeSniffer scripts (`phpcbf`, `phpcs`) is executed. Now they run from `vendor/bin` and so will reflect actual version of PHP.
- The resolved composer dependencies are now generated against PHP 8.1.

### [Beam]

- Added optional parameters for command `service:elastic-write-alias-rollover` to allow customization of search options for rollover. remp/remp#1208
  - `max-age` - triggers rollover after the maximum elapsed time from index creation is reached (default value is `31d`).
  - `max-size` -  triggers rollover when the index reaches a certain size (default value is `4gb`).
  - `max-primary-shard-size` - triggers rollover when the largest primary shard in the index reaches a certain size. Option introduced from elasticsearch version 7.13.

### [Campaign]

- Added `dimensions` of banner into required inputs during the creation of custom HTML banner. remp/remp#1165
- Added operator `is not` for the pageview attributes of campaign settings. remp/remp#1177
- Added the configurable option to prioritize banners on same position. To enable the feature set `env` variable `PRIORITIZE_BANNERS_ON_SAME_POSITION`. remp/remp#1167
- Added information about where to display to the detail page of campaign. remp/remp#1009
- Changed the form of editing banner's custom JS - now JS code is displayed formatted and linted in separate window. remp/remp#1171
- Added support for the inline editing of variables in the custom JS of banner. remp/remp#1172
- Replaced Laravel's internal `SerializableClosure` with the separate forked implementation to avoid deprecation. remp/remp#1160

### [Mailer]

- **IMPORTANT**: Changed primary keys of exposed tables from `int` to `bigint`. remp/remp#1187
  - This migration is a two-step process that requires your manual action - running `mail:migrate-mail-logs-and-conversions` in the off-peak hours. Since some tables are very exposed and cannot be locked for more than a couple of seconds, we decided to migrate the data into the new table manually and keep the old and new table in sync. Based on the amount of your data, the migration can take hours. Our production data with 200M+ records in the `mail_logs` table took ~15 hours to migrate.
  - The newly created `mail_logs` table also contains `user_id`, that will be used in the future release.
- **BREAKING**: Changed `DataRow` (which extended deprecated `Nette\Database\Table\IRow`) to `ActiveRowFactory` (which returns `ActiveRow`). remp/remp#1224
  - If you use `DataRow`, replace the use with `$this->activeRowFactory->create()`.
- **BREAKING**: Added types to the properties of `Remp\MailerModule\Models\Mailer\Mailer`. remp/remp#1224
  - If you extend this class (implement your own mailer), make sure you align your extended property definitions with the parent class.
- **BREAKING**: Changed order of constructor parameters of `Remp\MailerModule\Models\Mailer\Mailer`. remp/remp#1224
  - If you implement your own mailer, align your constructor with the parent class.
  - The registration of mailer in `config.neon` should use named parameter - e.g. change `addMailer(Remp\MailerModule\Models\Mailer\MailgunMailer(eu))` to `addMailer(Remp\MailerModule\Models\Mailer\MailgunMailer(code: eu))`.
- **BREAKING**: Updated `JsonMachine` library for parsing JSON streams. remp/remp#1224
  - If you extend Mailer and use `JsonMachine` to process the API response's JSON stream, please refer to the [current version of documentation](https://github.com/halaxa/json-machine/blob/fa261d25231c8bfe1ea0a29da9033f575d0860a8/README.md). 
- **IMPORTANT**: Fixed description of "priority" field in the newsletter list form. remp/remp#1195
  - The newsletter form incorrectly stated the information about newsletter list priority. Mailer always prioritized newsletters with higher "priority" field, but the form stated otherwise.
- Removed debugMode from CLI executions on non-production environments. remp/remp#1224
- Added `ServiceParamsProviderInterface` which implements adding custom parameters to mail templates. remp/remp#1175
- Added `DefaultServiceParamsProvider` which adds `settings` and `unsubscribe` params to mail templates.
- Refactored `Sender` class to use `ServiceParamsProviderInterface` instead of `generateServiceParams` method.
- Fixed `UnreadArticlesResolver` which crashed job processing in case of an uncaught (invalid URL) exception. remp/remp#1017
  - This could happen if an article was unpublished, but remained in the stats and was selected for personalized newsletter. Mailer wouldn't be able to fetch meta for article.
- Replaced use of `zrevrangebyscore` Redis call (deprecated) with `zrange` with `BYSCORE` and `REV` options. remp/remp#1195
- Changed required parameter `sorting` to optional in `/api/v1/mailers/mail-type-variants` API endpoint. remp/mnt#114
- Added `logRequestListenerErrors` option to `NewRelicModuleExtension` configuration. remp/remp#1180
- Added `getBool` method to MailerModule `EnvironmentConfig`, which provides support for getting boolean values from `.env` files. remp/remp#1180
- Fixed removal of template (Twig) variables in HTML email template when switching from text to WYSIWYG editor and back. remp/remp#719
- Fixed possibly slow view of job detail caused by missing index. remp/remp#1209
- Fixed mail preview when WYSIWYG editor was used. WYSIWYG editor changes structure of inserted HTML (removes html, head and body tags). We need to preserve these tags to preview mail. remp/remp#1194
- Added migration to change `mail_job_queue` `id` column type from `int` to `bigint`. remp/remp#1187

## [1.0.0] - 2022-09-26

### [Beam]

- **IMPORTANT**: Updated required PHP version to 8.0. remp/remp#1160

### [Campaign]

- **IMPORTANT**: Updated required PHP version to 8.0. remp/remp#1160

### [Mailer]

- **IMPORTANT**: Updated required PHP version to 8.0. remp/remp#1160

### [Sso]

- **IMPORTANT**: Updated required PHP version to 8.0. remp/remp#1160

---

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

[Unreleased]: https://github.com/remp2020/remp/compare/1.2.0...master
[1.2.0]: https://github.com/remp2020/remp/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/remp2020/remp/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/remp2020/remp/compare/0.34.1...1.0.0
