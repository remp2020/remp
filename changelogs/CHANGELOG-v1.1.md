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

---

[1.1.0]: https://github.com/remp2020/remp/compare/1.0.0...1.1.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker