## [4.0.0] - 2025-04-29

### [Beam]

- **BREAKING**: Updated Laravel to v12. remp/remp#1409
  - Please be aware that upgrade causes any assets published during Laravel's dependencies installation (`vendor:publish`) to have stricter rights by default (`0700` for directories, `0600` for files). You might need to update the rights of `public/vendor/` after the `composer install` call.
- **BREAKING**: Laravel changed its caching prefixes not to include `:` character automatically.
  - If you used `REDIS_PREFIX` env variable, we recommend to add `:` to your prefix to maintain consistency with the caching keys.
- **BREAKING**: Removed support for Airbrake/Errbit without replacement. The app still supports Sentry for error handling by default. remp/remp#1409
- **BREAKING**: Renamed Laravel's `FILESYSTEM_DRIVER` env variable to `FILESYSTEM_DISK`. remp/remp#1409
- **BREAKING**: Changed minimum Go version for building Tracker API and Segments API to 1.23.
- **BREAKING**: Removed `snowplow/referer-parser` dependency from PHP application. Beam wasn't using this anymore after removal of device stats. remp/remp#1409
- **BREAKING**: Replaced abandoned `laravelcollective/html` with `spatie/laravel-html`. remp/remp#1409
- **IMPORTANT**: Removed data of sections "Visitors" and "Google Analytics Reporting", which we removed in the previous version. remp/remp#1349
  - Migration removes tables `session_devices` and `session_referers`.
- Fixed banner preview components to include and run custom JS inside of iframe. remp/crm#3353
- Added option to specify API version for gender balance in env. remp/helpdesk#3303
- Fixed newsletter criteria not always selecting the desired number of articles. remp/respekt#378
- [Segments] Fixed missing mapping of some optional Elasticsearch fields (`subscribed`, `signed_in`, `revenue`). remp/remp#1394
  - Mapping is now pushed explicitly during Segments service startup.
  - Missed mapping may have caused problems when doing group by filter using untracked field.

### [Campaign]

- **BREAKING**: Updated Laravel to v12. remp/remp#1409
  - If you encounter issues with Showtime requests caused by `AbstractParser` of the device detector library, please purge the Redis cache with this command: `redis-cli -h REDIS-HOST -p REDIS-PORT -n REDIS-DEFAULT-DB keys 'Device*' | xargs redis-cli -h REDIS-HOST -p REDIS-PORT -n REDIS-DEFAULT-DB del`.
- **BREAKING**: Laravel changed its caching prefixes not to include `:` character automatically.
  - If you used `REDIS_PREFIX` env variable, we recommend to add `:` to your prefix to maintain consistency with the caching keys.
- **BREAKING**: Removed support for Airbrake/Errbit without replacement. The app still supports Sentry for error handling by default. remp/remp#1409
- **BREAKING**: Renamed Laravel's `FILESYSTEM_DRIVER` env variable to `FILESYSTEM_DISK`. remp/remp#1409
- **BREAKING**: Replaced abandoned `laravelcollective/html` with `spatie/laravel-html`. remp/remp#1409
- **BREAKING**: Removed code related to abandoned Pythia project. remp/remp#1409
- Added support for partial APCu caching, used when the PHP extension is installed and enabled. remp/remp#1409
  - Caching is now used only by device detection library and only if the extension is enabled. The idea is to ease on Redis cache.
- Refactored showtime caching to replace serialized objects with JSON cache. remp/remp#1401
- Added campaign targeting based on operating system. remp/remp#1403
- Refactored device and operating rules to their own class `DeviceRulesEvaluator`. remp/remp#1403
- Fixed partially broken inline banner selector. remp/remp#1406
  - Fixed image assets links and replaced old (not functional) unmaintained css selector finder library.
- Fixed bug that broke snippet (edit) form when backtick (``) character was stored within snippet content. remp/remp#1405

### [Mailer]

- **BREAKING**: Renamed `Remp\MailerModule\Models\Generators\ArticleLocker` to `Remp\MailerModule\Models\Generators\HtmlArticleLocker`. remp/novydenik#1324
  - If you work with the class directly, please update your includes.
- **BREAKING**: Renamed DI service `articleLocker` to `htmlArticleLocker`. remp/novydenik#1324
  - If you configure the service further, please update your `config.neon` file.
- **BREAKING**: Removed code related to abandoned Pythia project. remp/remp#1409
- Updated dependencies to their latest major versions.
- Added the ability to select the 'Subscription welcome email' and 'Unsubscribe goodbye email' option for any email that belongs to the newsletter, in addition to system emails. remp/remp#1388
- Fixed possible memory limit issues on list form factory. remp/remp#1404
- Changed image in `RespektContent` to be optional. remp/respekt#286, remp/respekt#386
- Fixed performance issue of batch processing in the "remove unsubscribed" steps. The altered query should perform better. remp/remp#1407
- Added support for UTM parameters to mail link replace. remp/respekt#377
- Added SnippetArticleLocker which provides way for generators to define lock messages via snippets. remp/novydenik#1324
- Added set `Content-Language` header in `Sender` when locale is specified. remp/remp#1410
- Fixed locale switch in template form. remp/remp#1400

### [Sso]

- **BREAKING**: Updated Laravel to v12. remp/remp#1409
- **BREAKING**: Laravel changed its caching prefixes not to include `:` character automatically.
  - If you used `REDIS_PREFIX` env variable, we recommend to add `:` to your prefix to maintain consistency with the caching keys.
- **BREAKING**: Removed support for Airbrake/Errbit without replacement. The app still supports Sentry for error handling by default. remp/remp#1409
- **BREAKING**: Renamed Laravel's `FILESYSTEM_DRIVER` env variable to `FILESYSTEM_DISK`. remp/remp#1409

---

[4.0.0]: https://github.com/remp2020/remp/compare/3.11.0...4.0.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
