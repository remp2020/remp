
## [0.34.1] - 2022-09-23

### [Mailer]

- Fixed issue with Twig syntax failsave which shouldn't allow user to save template with invalid syntax. It wasn't working correctly and allowed such scenario. remp/remp#1190

## [0.34.0] - 2022-09-23

### Project

- Updated Docker Adminer to the latest version.

### [Beam]

- Fixed edge case error in Newsletter sending when there are no articles matching the condition. remp/remp#1182
- Fixed broken newsletter form caused by unprocessable recurrent rules. remp/remp#1182

### [Campaign]

- Fixed incorrect links for schedules edit. remp/remp#1181
- Fixed banner custom css styles loading in preview. remp/remp#1183

### [Mailer]

- **BREAKING**: Changed allowed domains registration for content generator replacers. remp/remp#1176
  - Removed `addHost` public methods from replacers implementations (e.g. `UrlRtmReplace::addHost`) which served as a whitelist for hosts where RTM parameters should be added. Instead, use `AllowedDomainManager::addDomain` so the configuration is shared among all replacers.  See [README](https://github.com/remp2020/remp/tree/master/Mailer/extensions/mailer-module#allowed-domain-manager) for more information.
  - Added allowed domains check to `AnchorRtmReplace` content generator replacer to have allowed domains check in all replacers.
- Added `TextUrlRtmReplace` content generator replacer to add RTM parameters to links in the text version of the email. remp/remp#1176
- Fixed crashing Newsfilter generator if `articlelink` tag pointed to the article that doesn't exist. remp/remp#1066
- Refactored mail generators wordpress article links parsing. remp/remp#1166
- Added `context` to job create/detail pages. remp/remp#1185
  - Context is a parameter, that maintains that user doesn't receive contextually similar emails multiple times. Each user can receive an email with a specific context only once.
- Fixed bug where template parameters used in URLs would not be correctly replaced if Mailgun sender was used in batch mode. hiking.sk/web#92
- Fixed issue with `worker:mail` getting stuck in an infinite "no longer top priority, switching" message. remp/remp#1189

## [0.33.0] - 2022-08-08

### Project

- **IMPORTANT**: This is the last version supporting PHP 7.4. Future versions will require upgrade to PHP 8.0.
- Added support for `number` and `text` DataTables renderers. remp/remp#852

### [Beam]

- Added support for `group` in options for `ButtonSwitcher`. Groups allow to visually separate options. remp/remp#1110
- Added `First day`, `First 7 days` and `First 14 days` options to `PageLoadsGraph`. remp/remp#1110
- [Tracker]: Added support for SASL Kafka authentication in Tracker. remp/remp#971
- Added overall internal pageviews count to article title ab testing histogram. remp/remp#1125
- Changed commands called in `pageviews:aggregate` command to aggregate pageviews into 20-minute intervals. remp/remp#652
  - Due to the interval change, duplicates may arise in `article_pageviews`. We recommend running `data:delete-duplicate-pageviews` command after migration to clean up data.
- Added `pageviews` data source to load data for time histogram for specific article. remp/remp#652
- Added option to switch between `pageviews`, `snapshots` and `journal` data sources in article page loads graph on article detail page. remp/remp#652
- Fixed site layout on mobile.

### [Campaign]

- Added option to toggle between mobile and desktop banner preview on banner edit page. remp/remp#1071
- Fixed bar banner close text display. remp/crm#1068
- Fixed site layout on mobile.
- Added improvements to the campaign schedules view page. remp/remp#1158

### [Mailer]

- **IMPORTANT**: Added soft delete for emails, layouts, newsletter lists and generator templates. remp/remp#1075
  - The database migration is blocking to maintain consistency and might take 5-10 minutes for bigger instances. Please schedule the release to the off-peak hours.
- **IMPORTANT**: Fixed mail type priority can't be 0. remp/remp#1134
  - Added validation to add/edit newsletter list form and `JobPresenter::handleSetBatchReadyToSend`, `JobPresenter::handleSetBatchSend` methods.
  - Added migration to update `mail_types` with priority 0 to default value.
- Fixed logs subject not storing the translated version of sent email. remp/remp#1130
- Fixed mixed up columns "Locked" and "Publicly listed" in Newsletter lists table. remp/remp#1131
- Fixed deprecated warning because of using `Latte#addFilter()` method. remp/remp#1135
- Fixed the filter loading issue. remp/remp#1135
- Fixed Latte deprecation issues introduced in the latest version of the templating engine. remp/remp#1136
- Removed captions from the submit buttons in favor of HTML content with icons. remp/remp#1138
- Fixed fallback for non-batch sending in `mail:worker` command. remp/remp#1139
  - If the worker was run with `--batch` flag and the configured mailer didn't support batch sending, the fallback would execute incorrect branch and the sending would end up with error.
- Changed the hash function generating Sender-ID header.
- Added trait for processing dates to the application's default timezone before they're used in the selection or insertion. remp/remp#1144
- Fixed bug possibly causing conversions happening early after the job was created not to be attributed to the job correctly. remp/remp#1144
- Fixed "Invalid datetime format" bug with hermes error logging to the database. remp/remp#1145
- Fixed Mailgun API error with wrong `recipient-variables` parameter. remp/remp#1146
- Fixed API triggering unnecessary session initiation. remp/remp#1149
- Fixed possibility of stuck MySQL query when removing unsubscribed users from the mail job queue. remp/remp#1148
- Added mail generator for Napunk to overide default slovak email lock message. remp/remp#1129
- Changed the behaviour of mail template form - template content is editable after general information is saved. remp/remp#1122
- Refactored mailer batches to allow templates from only one mail type. remp/remp#1140
  - Fixed decide sending mailer in `MailWorkerCommand` depending on template mail type.
- Fixed type error authentication issue when Mailer uses REMP SSO for authentication. remp/remp#1161
- Fixed internal content parsers which could throw notices if parsed URL doesn't contain JSON schema. remp/remp#1162
- Added support for allow listing emails in Mailer. remp/remp#1150
  - Added tests for application `Sender` and `EmailAllowList` classes.
- Fixed site layout on mobile.
- Fixed snippets in text versions of emails (HTML versions were inserted instead of text). remp/remp#1164
- Changed the batch form action buttons to improve users experience. remp/remp#1157

### [Sso]

- Fixed site layout on mobile.

## [0.32.2] - 2022-05-11

### [Campaign]

- Added referrer policy (`no-referrer-when-downgrade`) to banner links. remp/remp#1123
  - Default referrer policy in browsers is now stricter and we were not providing correct (whole) referrers to target pages.

### [Mailer]

- Added option to select the locale of testing email. remp/remp#1118
- Added alert message about missing template translation. remp/remp#1118
- Added `from` field of template between the translated items of template. remp/remp#1119
- Fixed warning in the snippet form after submitting invalid values. remp/remp#1124
- Added info about default content of translated items. remp/remp#1126
- Fixed missing horizontal scrolling in the template code editor. remp/remp#1116
- Fixed accidental breaking change in `/api/v1/mailers/mail-types` causing `code` filter not to work anymore. remp/remp#1127
  - New features of the API including the breaking change were now moved to the `/api/v2/mailers/mail-types`.

## [0.32.1] - 2022-05-02

### [Beam]

- Fixed IOTA issue with reading progress not being loaded on non-article pages. remp/remp#1096
- Fixed dashboard refresh issue if multiple landing pages were present on the dashboard. remp/remp#1112
- [Tracker] Added support for Google Cloud Pub/Sub message broker as replacement for Kafka. remp/remp#1097

### [Campaign]

- Added navigation links within the forms and UI to simplify following paths between banners and campaigns. remp/remp#1099

### [Mailer]

- Added support for `greybox` tag in generators. remp/remp#1060
- Added `List-Unsubscribe` header to outgoing emails. remp/remp#813
- Changed batch email generator filter strategy. remp/remp#1086
  - Split complex `DELETE` queries in filter job queue functions into two step queries: `SELECT` array of ids to remove and simple `DELETE` query. In this way we want to prevent from potential deadlock that may occur in complex `DELETE` queries.
- Added support for configuration of goodbye email for unsubscribed users. remp/remp#1063
  - Added `unsubscribe_mail_template_id` into the `mail_types` table.
  - Added `unsubscribe_mail_template_code` parameter to the `/api/v1/mailers/mail-type-upsert` API to configure the welcome email.
  - Added new field for the subscription goodbye email in the newsletter edit/create form that provides system emails for selection.
  - Changed signature of `UserSubscriptionsRepository::unsubscribeUser()` and `UserSubscriptionsRepository::unsubscribeUserVariant()` - new `$sendGoodbyeEmail` argument was added.
- Added new emit of `user-unsubscribed` hermes event when user unsubscribes from the newsletter. remp/remp#1063
- Added retry ability to the hermes handler sending one-time emails. remp/remp#935
  - Until now, if there was an error during sending (Mailgun was unavailable, network was down), Mailer didn't try to send the email again.
- Fixed bug in `/api/v1/users/is-unsubscribed` response generation, altering response schema. remp/crm#1100
  - The endpoint never correctly generated the response, and it needed to be altered to be valid JSON. Since the endpoint never worked correctly, we mark this as bugfix and not a breaking change.
- Changed `/api/v1/mailers/mail-types` API to allow multiple `code` params to be specified as a filter. remp/crm#2387
- Added filtering by `mail_type_category_code` to the `/api/v1/mailers/mail-types` API. remp/crm#2387
- Added support for the localization of emails. remp/remp#1085
  - The UI stays same until you add secondary locale in the configuration of `Remp\MailerModule\Models\Config\LocalizationConfig`.
  - Emails are sent in default configured locale until the translation is available in secondary configured locale.

## [0.31.0] - 2022-03-14

### [Beam]

- Added support for displaying canonical URLs for non-article pageviews on Beam dashboard. remp/remp#832
- Added missing style (`display:relative`) in IOTA template badge. remp/web#1736
- Added caching mechanism for dashboard values (unique browser count) to avoid unnecessary requests for non-critical values. remp/remp#1090
  - The values are now periodically refreshed in the background every minute.

### [Mailer]

- Added configurable permission manager currently allowing limiting access to batch execution. See [README](https://github.com/remp2020/remp/tree/master/Mailer/extensions/mailer-module#permission-management) for more information. remp/remp#1087
- Added HTML beautifier for HTML content of emails. remp/remp#684
  - WYSIWYG used to generate unformatted HTML which was hard to edit. All emails are now automatically beautified when they're edited.
- Added email fullscreen edit option for medium and wider devices. remp/remp#678
  - This feature is available in email edit page for both HTML and WYSIWYG editor

## [0.30.2] - 2022-02-26

### [Beam]

- Fixed XSS vulnerability on the SSO authentication error callback page.

### [Campaign]

- Fixed XSS vulnerability on the SSO authentication error callback page.

### [Sso]

- Fixed XSS vulnerability on the SSO authentication error callback page.

## [0.30.1] - 2022-02-22

### [Beam]

- Fixed retrieval of browser_id in `conversions:aggregate-events` command which leads to more thorough definition of user's conversion path. remp/remp#1049
  - Previously some events (mainly pageviews) could have been not matched correctly and missing in the aggregated data.
- Fixed occasional incorrect page_progress parameter being tracked causing progress update not to be tracked at all.
  - Due to JS floating points being JS floating points sometimes the page_progress was >1 which server refused to accept.
- Fixed issues with very slow author/section segment recalculation for instances with bigger amount of data. remp/remp#1088

## [0.30.0] - 2022-02-10

### Project

- Bumped reference version of Elasticsearch and Kibana to 7.17.0.

### [Beam]

- Fixed possibly too broad scope of IOTA requests. remp/remp#1050
  - If the articleSelector didn't match any articles, request was made without an `article_id` filter which could cause temporary Elastic unavailability.
- Added option to configure `--step=` of `pageviews:aggregate-articles-views` command to avoid Elasticsearch's _"Trying to create too many buckets"_ error. remp/remp#1050

### [Campaign]

- **BREAKING**: Removed loading `Noto Sans` and `Noto Sans Serif` fonts from campaign banner previews and use default system serif and sans-serif fonts. remp/remp#1041
  - You can change used fonts by adding `font-face` style to `.remp-banner .serif` and `.remp-banner .sans-serif` classes.
- Added custom configuration for `CampaignController::showtime` Sentry sample rate. remp/remp#1029

### [Mailer]

- **BREAKING**: The `/api/v1/mailers/send-email` API now validates context separately for each email address. This is a bugfix, but we label it as breaking because someone could depend on this behavior. remp/crm#2226
  - Previously the handler validated context globally, which was not intentional. It could have prevented a notification with the same context to be sent to different users. This change unifies the behavior with jobs - they checked the email-context pair since the beginning.
- **BREAKING**: Removed obsolete column `is_public` from `mail_types` table also from related code and API call. remp/remp#1061
  - Check your usage of `is_public` column - replace with `public_listing` column or remove.
  - The signature of method `ListsRepository::add()` changed. Check your usages of the method and incorporate the changes.
- Fixed inconsistent `PageMeta` use where constructor allowed to enter nullable values if they weren't present, but getter didn't allow to return them. remp/remp#1055
- Fixed incorrect handling of return values (null vs bool) caused by Nette 3.0 upgrade. remp/remp#1057
- Added checkbox for hiding newsletter from public newsletter settings. remp/remp#1025
- Fixed WP-based generator issue causing unnecessary `<br />` tags being appended to the `<a>` links. remp/remp#1065
- Fixed error in `ArticleUrlParserGenerator` caused by invalid URL. Mailer would crash instead of displaying error to the user. remp/remp#1066
- Added migration to add index on `email` column in `autologin_tokens` table. remp/remp#1067
- Added options to specify `variant_code` in the subscribe/unsubscribe APIs to complement subscription through `variant_id`. remp/crm#2212
- Added support for managing roles and privileges to Mailer. See [README](./Mailer/extensions/mailer-module/README.md#permission-management) for more information. remp/remp#1087
- Refactored mailer log filter (because of performance issues). remp/remp#1095
- Added `allowSearch` setting to DataTable component. remp/remp#1095
- Created `NewrelicModule` extension. remp/remp#1115
  - Added `NewrelicRequestListener` which handles naming request transactions.

## [0.29.0] - 2021-11-18

### Project

- Removed obsolete `python-minimal` from Dockerfile to fix build error. remp/remp#1012
- Bumped reference version of Elasticsearch and Kibana to 7.15.2. remp/remp#1043
- Bumped reference version of Go in Docker images to 1.17.4.

### [Beam]

- Added article content type filter to authors. remp/remp#1001
- Fixed missing search bar on mobile devices. remp/remp#932
- Added support for Redis Sentinel cluster in the app configuration. remp/remp#1035
  - Added new `REDIS_SENTINEL_SERVICE` environment variable to configure name of the Sentinel service. If used, sentinel hosts are expected to be configured in comma-separated `REDIS_URL` environment variable.
- Added `article_external_id` into response of API call `/api/conversions`. remp/remp#1031
- Added information about article's tags, authors and sections into API call `/api/articles`. remp/remp#1031
- Property filter now correctly filters all sections of Beam, not just the main dashboard data. remp/remp#987
- Added `api/articles/read` API to list already read articles. remp/remp#1030

### [Mailer]

- **BREAKING**: Changed mail job batch status `STATUS_READY` to `STATUS_READY_TO_PROCESS_AND_SEND` in `BatchesRepository`. remp/remp#995
  - If you use `STATUS_READY` in your implementation, replace it with `STATUS_READY_TO_PROCESS_AND_SEND`.
- **BREAKING**: Added parameter for `code` attribute of mail type category into `ListCategoriesRepository::add()` method. remp/remp#675
  - The signature of method changed from `(string $title, int $sorting)` to `(string $title, string $code, int $sorting)`. Check your usages of the method and incorporate the changes.
- **BREAKING**: Changed initialization of DI services using Redis. If you use any of the following services, please amend your initialization in your `config.neon`. remp/remp#1035
  - Change `Tomaj\Hermes\Shutdown\PredisShutdown(@redisCache::client())` to `Tomaj\Hermes\Shutdown\PredisShutdown(@redisClientFactory::getClient())`
  - Change `Remp\MailerModule\Hermes\HermesTasksQueue(%redis.host%, %redis.port%, %redis.db%)` to `Remp\MailerModule\Hermes\HermesTasksQueue`
  - Change `Remp\MailerModule\Models\Job\MailCache(%redis.host%, %redis.port%, %redis.db%)` to `Remp\MailerModule\Models\Job\MailCache`
  - Change `Remp\MailerModule\Models\HealthChecker(%redis.host%, %redis.port%, %redis.db%)` to `Remp\MailerModule\Models\HealthChecker`
- **BREAKING**: Removed class `Remp\MailerModule\Models\RedisCache` in favor of `Remp\MailerModule\Models\RedisClientFactory`. remp/remp#1035
  - If you used `RedisCache` in your extensions, replace it with the use of `RedisTrait` and `RedisClientFactory`.
- **BREAKING**: Added flag to include deactivated users into interface method `Remp\MailerModule\Models\Users\IUser::list()`. remp/crm#1392
  - Default state is same as before _(returned only active users)_.
  - This is breaking change because now interface suggests that only active users are returned.
  - If you have own implementation of interface `IUser`, you should add new flag `$includeDeactivated` and handle it accordingly.
- Added new mail job batch status `STATUS_READY_TO_PROCESS`. remp/remp#995
- Added option to process mail job batch and get number of emails that will be sent in that batch. New button added to every mail job batch available when mail job batch is in `created` status. remp/remp#995
- Added `mail:remove-old-batches` command that removes mail job batches in `processed` status older than 24 hours. This prevents from using outdated emails set to send emails. remp/remp#995
- Added the prefilling of from field into ArticleUrlParserWidget after email's type is selected. remp/remp#999
- Fixed Article URL parser generator to ignore blank lines causing NULL requests to parse the URLs. remp/remp#1014
- Removed obsolete RTM campaign parameter from Article URL parser generator.
- Added support for configuration of welcome email for new newsletter subscription. remp/remp#675
  - Added `subscribe_mail_template_id` into the `mail_types` table.
  - Added `subscribe_mail_template_code` parameter to the `/api/v1/mailers/mail-type-upsert` API to configure the welcome email.
  - Added new field for the subscription welcome email in the newsletter edit/create form that provides system emails for selection.
  - Changed signature of `UserSubscriptionsRepository::subscribeUser()` - new `$sendWelcomeEmail` argument was added.
- Added `code` column into the `mail_type_categories` table. Migration will take care of adding codes into the already existing categories. remp/remp#675
  - Added respective `code` attributes for mail type categories in `DatabaseSeedCommand`.
- Added new emit of `user-subscribed` hermes event when user subscribes to the newsletter. remp/remp#675
- Added optional boolean `send_accompanying_emails` parameter into the `/api/v1/users/subscribe` and `/api/v1/users/bulk-subscribe` API endpoints. remp/remp#675
  - This parameter configures whether the subscription of newsletter should also trigger the welcome (and in the future goodbye) email for the newsletter.
- Changed Hermes `RedisDriver` sleep time from 5 seconds to 1 second. This should speed up some asynchronous operations. remp/crm#2046
- Fixed possible notice caused by missing `source_template_id` in the `ArticleUrlParserTemplateFormFactory`. remp/remp#1024
- Changed implementation of deprecated `Tomaj\NetteApi\Misc\BearerTokenRepositoryInterface` in favor of `Tomaj\NetteApi\Misc\TokenRepositoryInterface`. remp/crm#2052
- Added possibility to send B version of subject from `ArticleUrlParser`. remp/remp#982
- Changed how unique template code is acquired - instead od suffixing numbers, Mailer now appends random string to the end of mail template code. remp/remp#1027
  - All internal parts of Mailer which didn't use this feature and tried to get the code their own way now use provided `TemplatesRepository::getUniqueTemplateCode()` method.
- Removed unused repositories `LogEventsRepository` and `UsersRepository` _(leftovers after separation from CRM)_.
- Added API endpoint `/api/v1/users/delete` to remove all user data for provided email. remp/crm#1392
  - Added helper class `UserManager` with method `deleteUser()` to manage user deletion. remp/crm#1392
- Search bar can be toggled on mobile devices. remp/remp#932
- Improved memory footprint of `Remp\MailerModule\Models\Users\Crm::list` method by decoding JSON in stream. remp/remp#1040
- Added new command `mail:sync-deleted-users` (`SyncDeletedUsersCommand`) to handle deletion of emails which are not present in CRM _(loads users from implementation of `Remp\MailerModule\Models\Users\IUser` interface)_. remp/crm#1392

### [Campaign]

- Fixed missing search bar on mobile devices. remp/remp#932
- Fixed sorting issues on campaigns listing for multi-value columns (sorting was disabled). remp/remp#1034
- Added banner variant and segment filter to campaigns listing. remp/remp#1034
- Added support for Redis Sentinel cluster in the app configuration. remp/remp#1035
  - Added new `REDIS_SENTINEL_SERVICE` environment variable to configure name of the Sentinel service. If used, sentinel hosts are expected to be configured in comma-separated `REDIS_URL` environment variable.
- Fixed banner and campaign listings search. Grid was not able to search within campaign names (which is the main point of this search). remp/remp#1038
- Added `SENTRY_SHOWTIME_SAMPLERATE` env variable to configure sample rate of showtime logs/errors. remp/remp#1029
- Fixed campaign segments deleting when copying campaigns. After copying and creating a new campaign, the original had deleted segments originally assigned to it. remp/remp#1037

### [Sso]

- Fixed missing search bar on mobile devices. remp/remp#9322
- Added support for Redis Sentinel cluster in the app configuration. remp/remp#1035
  - Added new `REDIS_SENTINEL_SERVICE` environment variable to configure name of the Sentinel service. If used, sentinel hosts are expected to be configured in comma-separated `REDIS_URL` environment variable.

## [0.28.0] - 2021-09-09

### [Beam]

- **BREAKING**: Removed auto-enabling of AIRBRAKE error logging in case `AIRBRAKE_ENABLED` is missing. remp/remp#994
- *remplib.js* - added option to track article's `contentType` when tracking pageviews. remp/remp#988
  - **Breaking**: Derived parameter `is_article` in Elastic storage is set to `true` only if value of `contentType` is set to `'article'`. Previously, all tracked articles had `is_article` value set to `true`- this may affect Beam segments that worked with `article: true` rule or users processing `is_article` parameter in raw Elastic data.
- Refactored beam `CompressAggregations` command to run in chunks because of colliding database transactions with `AggregatePageviewLoadJob` command, which caused deadlock. remp/remp#944
- Added `content_type` filter to the `api/articles/unread` API to exclude unwanted content types. remp/remp#973
- Added support for remplib.js reinitialization, necessary for correct execution in single-page apps. See [README](./Beam/README.md#single-page-applications) for more information. remp/remp#968
- Fixed broken `ArticleSeeder` and `EntitySeeder`.
- Fixed possibility of an error on the articles grids if filter matched too many articles. remp/remp#977
- Added `SameSite=Lax` attribute to all cookies set by `remplib.js`. Missing attribute could possibly lead to issues on Safari, which doesn't defaults to `Lax` like other browsers. remp/remp#957
- [Tracker] Added option to limit tracked time spent for one pageview. Set tracker's ENV variable `TRACKER_TIMESPENT_LIMIT` to desired pageview tracking threshold (in seconds). Helps to filter out tracking of articles opened for too long _(forgotten browser window on different workspace/monitor acts as active in some browsers)_. remp/remp#242
- [Tracker] Added support for canonical URL tracking to complement full URL tracking. If it's not found in the HTML, no canonical URL is stored and only regular URL is tracked. remp/remp#988
- Added tooltip to the user path chart. remp/remp#551
- Updated Docker Telegraf configuration to include `canonical_url` in the concurrents data. This will be necessary in the future to correctly display non-article traffic on the main dashboard. remp/remp#472
- Changed output of `service:elastic-data-retention` Beam command to correctly reflect if index was deleted or not. remp/remp#940
- [Segments] Fixed ignoring of segment's `active` flag in user/browser segment presence API check. remp/remp#1007
  - The bug caused that it was possible to check presence of users/browsers in segments even if the segment was not active.

### [Campaign]

- **BREAKING**: Removed auto-enabling of AIRBRAKE error logging in case `AIRBRAKE_ENABLED` is missing. remp/remp#994
- Fixed caching of Newsletter rectangle banner, which broke after the recent framework updates and caused configuration not to be available at the time of banner rendering. remp/remp#959
- Changed default stats view to include 30 days of data instead of 2 to allow bigger picture in campaign evaluation by default. remp/remp#969
- Added support for remplib.js reinitialization, necessary for correct execution in single-page apps. See [README](./Campaign/README.md#single-page-applications) for more information. remp/remp#968
- Fixed unnecessary storing of empty URL/referer filters of "Where to display" section in campaign configuration. remp/remp#975
- Added `SameSite=Lax` attribute to all cookies set by `remplib.js`. Missing attribute could possibly lead to issues on Safari, which doesn't defaults to `Lax` like other browsers. remp/remp#957
- Added `rtmSource` to campaign's custom JS params, so clients can correctly track events without hardcoding the `rtmSource` to some arbitrary value.
- Added support for global campaign/banner variables. See [README](./Campaign/README.md#variables) for more information. remp/remp#972
- Fixed missing `variables` template param in `BannerController->copy` method. remp/remp#991
- Fixed inefficient querying of campaign active status on campaign listing. remp/remp#1000
- Added support for pageview attributes to showtime request and added ui for configuring pageview attributes to campaign form. See [README](./Campaign/README.md#javascript-snippet) for more information. remp/remp#986
- Added error logging from showtime experiment `showtime.php` into `laravel.log`. remp/remp#994
- Added support for Sentry error logging from showtime experiment `showtime.php`. remp/remp#994

### [Mailer]

- **BREAKING**: Renamed `UrlParserGenerator` to `ArticleUrlParserGenerator`. remp/remp#949
  - Check your configuration if you are registering this generator.
- **BREAKING**: Finalized refactoring of `Remp\MailerModule\Repositories\IConversionsRepository` interface, removed obsolete methods. remp/remp#907
  - Methods `getBatchTemplatesConversions` and `getNonBatchTemplateConversions` were removed, because they encouraged suboptimal (non-time-constrained) implementation.
  - In your implementation replace them with newly added `getBatchTemplatesConversionsSince` and `getNonBatchTemplatesConversionsSince` respectively.
- **BREAKING**: Removed public preview URL specified by template code. remp/remp#581
  - **IMPORTANT**: The database migration can take up to 5-10 minutes, depending on the number of mail templates you currently have. Our testing migration with 100K templates took around 10 minutes.
  - Use replacement public preview URL specified by random string (so it's not guessable).
  - If you need to obtain HTML of email via template code, you can use newly added `/api/v1/mailers/render-template` API.
- Changed encoding of `mail_logs.subject` column to `uft8mb4_unicode_ci` to match encoding of `mail_templates.subject`. remp/remp#984
  - **IMPORTANT**: If you have more then 50M records in the `mail_logs` table, the `MailLogsSubjectEncoding` migration can be time-consuming. Consider raising your deploy timeout limits or mark the migration as complete and run the queries manually. Our testing migration with ~20M records took 3 minutes.
- Added email generator `ShopUrlParserGenerator` to get informations about products. remp/remp#949
- Fixed broken new email template page when no layout or newsletter list was defined.
- Added `SimpleAuthenticator`, which keeps plain list of emails and passwords that are valid to log in. Mailer can use this authenticator (instead of e.g. Sso `Authenticator`) to make it work without an external authentication system.
- Added support for `ignore_content_types` parameter in `UnreadArticlesResolver` used in generated/personalized e-mails. Parameter excludes articles of certain content types and avoids their use in generated e-mails. remp/remp#973
- Added public preview for emails (templates), accessible without authentication. Preview link is accessible in each email detail eg.: _(http://mailer.remp.press/template/show/1)_.  remp/remp#581
- Added API endpoint `/api/v1/mailers/render-template`. Returns a rendered HTML email. remp/remp#581
- Added widget for `ArticleUrlParserGenerator`. remp/remp#946
- Added demo user subscriptions in `demo:seed` command.
- Added the check of cache to prevent display campaigns to users excluded from campaigns. remp/remp#833
- Fixed job unsubscribe stats inconsistency. remp/remp#993
  - Job detail (left panel) could display unsubscribes for non-related newsletter lists unsubscribed along with the sent newsletter.
- Added `ApplicationStatus` component, displaying online/offline status of Mailer workers. As follow up, `MissingConfiguration` component was removed and its functionality was merged into `ApplicationStatus` component. remp/remp#985

### [Sso]

- **BREAKING**: Removed auto-enabling of AIRBRAKE error logging in case `AIRBRAKE_ENABLED` is missing. remp/remp#994

## [0.27.1] - 2021-07-08

### [Beam]

- Fixed search issues on the author detail page. remp/remp#965
- Fixed missing journal dependency in the `/api/articles/unread` API. remp/remp#966

### [Campaign]

- Fixed type error issue in Campaign's showtime request if they were hit directly without any parameters. App now returns correct JSON error. remp/remp#964
- Fixed yarn lockfile issue causing installations with `--frozen-lockfile` option to fail.

### [Mailer]

- Added `page_url` to the response resources of the `/api/v1/mailers/mail-types` API. remp/crm#1946

## [0.27.0] - 2021-06-29

### Project

- Added `--explicit_defaults_for_timestamp` switch to the MySQL docker command to avoid unpredictable behavior when creating database tables - MySQL would set the default `CURRENT_TIMESTAMP` to the first date column of each table. Make sure your production settings match to avoid issues.

### [Beam]

- **BREAKING**: Remplib.js is not automatically storing any query param to storage anymore. Only `rtm_*` keys and keys explicitly specified in `rempConfig.storageExpiration` are allowed. remp/remp#950
  - This only affects you if you rely on this behavior and expect to find any query parameters in the cookie/local_storage during the visit.
- Changed scheduled commands to run in background. remp/remp#942
- Added `AggregatePageviews` command which groups article timespent/load commands. remp/remp#942
- Fixed possibly invalid aggregation of conversion data which caused time columns to be off due to the timezone issues. remp/remp#464
  - We decided to truncate all of the aggregations (they're temporary, they would be removed eventually) and trigger the aggregation internally again. You might see higher load after the release caused by `conversions:aggregate-events` and `conversions:process-sources` commands.
- Added `/api/pageviews/histogram` API endpoint to get pageviews histogram for selected date range. See [README.md](./Beam/README.md) for more details. remp/remp#953
- [Segments]: Fixed "Trying to create too many scroll contexts" error caused by the amount of opened scrolls in Elastic and loose close timeouts which could happen on selected queries. remp/remp#464
- Fixed issue with API calls generating PHP sessions on each request, causing session store to be overloaded with records. remp/remp#954
- Added support for article external ID in the global search. remp/remp#955
- Fixed account listing broken in the latest release. remp/remp#958

### [Campaign]

- Introduced `public_id` row to reach unique identifications with shorter string. remp/remp#916
- Replaced `uuid` campaign's identification with shorter `public_id` in cookies and local storage. remp/remp#916
- Fixed issue with API calls generating PHP sessions on each request, causing session store to be overloaded with records. remp/remp#954

### [Mailer]

- **BREAKING**: Extended `IConversionsRepository` interface with `getBatchTemplatesConversionsSince()` function. remp/remp#907
- **BREAKING**: Proprietary Mailer classes' namespace was changed from `Remp/MailerModule` prefix to `Remp/Mailer`. In the future, these classes may be completely removed from the repository. If you have previously relied on their functionality, make sure you reflect on this change and ideally copy the functionality into your own code. remp/remp#924
  - List of affected widgets: `DennikeWidget`, `MediaBriefingWidget`, `MMSWidget`, `NewsfilterWidget`, `NovydenikNewsfilterWidget`, `TldrWidget`
  - List of affected form factories: `DennikeTemplateFormFactory`, `MediaBriefingTemplateFormFactory`, `MMSTemplateFormFactory`, `NewsfilterTemplateFormFactory`, `NovydenikNewsfilterTemplateFormFactory`, `TldrTemplateFormFactory`
  - List of affected generators: `DailyNewsletterGenerator`, `DennikeGenerator`, `DennikNBestPerformingArticlesGenerator`, `MediaBriefingGenerator`, `MinutaAlertGenerator`, `MinutaDigestGenerator`, `MMSGenerator`, `NewsfilterGenerator`, `NovydenikNewsfilterGenerator`, `TldrGenerator`
  - `ContentInterface` implementations: `DenniknContent`, `NovydenikContent`, `TyzdenContent`
- **BREAKING**: Handler confirming CRM user `Remp\MailerModule\Hermes\ConfirmCrmUserHandler` is replaced with `Remp\MailerModule\Hermes\ValidateCrmEmailHandler`. remp/crm#1740
  - This conforms with the latest changes in the CRM which splits user confirmation and email validation flagging. If you used this handler in your `config.local.neon`, please replace it with the new one.
  - This version requires CRM 0.32.0+, otherwise the confirmation APIs would return HTTP 404. After the upgrade, make sure the API key used to communicate with CRM has access to the `Users:EmailValidationApi` API by visiting `/api/api-access-admin/` in the CRM.
- Added missing login error messages if REMP CRM is used to authenticate the user.
- Added option to identify source template by code in `MailGeneratorPreprocessHandler`. remp/remp#941
- Fixed `ProcessConversionStatsCommand` to process all conversions occurred after selected time, not only for mail job batch templates created after selected time. remp/remp#907
- Added `mail_from` into email's type options to make easier the mail composition. remp/remp#952
- Added `twig/intl-extra` extension to allow using more filters as `format_currency` etc. See https://github.com/twigphp/intl-extra for another filters. remp/remp#829

### [Sso]

- Fixed issue with API calls generating PHP sessions on each request, causing session store to be overloaded with records. remp/remp#954

## [0.26.0] - 2021-06-10

### [Beam]

- **BREAKING**: Upgraded to Laravel 8. remp/remp#491
  - Make sure that when including `remplib` library (built with production settings), your HTML document has defined character encoding, otherwise `remplib` might not be initialized correctly. Character encoding can be specified using `meta` tag, e.g. `<meta charset="UTF-8" />`.
- Fixed sorting of referer stats in the article detail. The default sorting is now again Visits count. remp/remp#934
- Changed default order sequence for numeric columns to be descending first. remp/remp#934
- Fixed incorrect pageviews count bug in `/top` APIs when using filters. remp/remp#937
- Added optional `APP_TRUSTED_PROXIES` environmental variable. IP/HTTP related headers will only be allowed from trusted proxy. All proxies are trusted by default.
- Added v2 of `/top` APIs. See [README.md](./Beam/README.md) for more details. remp/remp#938

### [Campaign]

- Upgraded to Laravel 8. remp/remp#491
- Changed showtime experiment to be enabled by default. This should change make showtime requests much faster by bypassing Laravel in very exposed endpoint. remp/remp#939
  - If you want to fallback to the original implementation, use `rempConfig.campaign.showtimExperiment = false` in the remplib JS configuration.
- Added optional `APP_TRUSTED_PROXIES` environmental variable. IP/HTTP related headers will only be allowed from trusted proxy. All proxies are trusted by default.

### [Mailer]

- Removed deprecated column in `mail_user_subscriptions` table.
- Added new hermes event `batch-status-change` to emit when mail job batch status is changed. remp/remp#660
- Added `TrackNewsletterArticlesHandler` to track articles sent in newsletters. remp/remp#660
- Fixed slow processing of batches if the target segment was bigger - added missing index. remp/remp#947

### [Sso]

- Upgraded to Laravel 8. remp/remp#491
- Added optional `APP_TRUSTED_PROXIES` environmental variable. IP/HTTP related headers will only be allowed from trusted proxy. All proxies are trusted by default.

## [0.25.1] - 2021-06-01

### [Beam]

- Fixed article upsert API bug if tag with categories is updated. remp/remp#931

## [0.25.0] - 2021-05-28

### [Beam]

- **BREAKING**: Upgraded to Laravel 7. remp/remp#491
  - All API endpoints now return dates in ISO-8601 compatible format. Make sure all code consuming the Beam API is accustomed to this change. For example, previously, date would serialized like the following: `2019-12-02 20:01:00`. Now it is serialized like `2019-12-02T20:01:00.283041Z` (always in UTC).
  - Environment variable `APP_SESSION_EXPIRATION` was renamed to `SESSION_LIFETIME`.
- Added `/api/articles` list articles info api endpoint. remp/remp#909

### [Campaign]

- **BREAKING**: Upgraded to Laravel 7. remp/remp#491
  - All API endpoints now return dates in ISO-8601 compatible format. Make sure all code consuming the Campaign API is accustomed to this change. For example, previously, date would serialized like the following: `2019-12-02 20:01:00`. Now it is serialized like `2019-12-02T20:01:00.283041Z` (always in UTC).
  - Environment variable `APP_SESSION_EXPIRATION` was renamed to `SESSION_LIFETIME`.
- Fixed broken campaign comparison. remp/remp#926
- Fixed broken campaign copying. remp/remp#927

### [Mailer]

- Changed calculation of subscriber values for newsletter list dashboard and detail charts. Instead of the latest value of each day, max value of the day is now used. This significantly improved the dashboard performance. remp/remp#928
- Added newsletter subscribers list into the newsletter detail section. remp/remp#873
- Changed email filter to use fulltext search for `mail_body_html`. remp/remp#595
  - **WARNING**: The migration adding the fulltext index to speed up the search can take longer than usual. Testing migration lasted ~5 minutes for 1GB of `mail_templates` MySQL table data (~100K rows). The table is locked for writes during the migration and Mailer will not be able to create/update emails during the migration period. Please release this version in less exposed time.
- Added sorting inputs to mail source template form and use ascending sorting. remp/remp#918
- Added `code` to mail layouts. Code of existing layout was generated based their ID and name. remp/remp#917
- Added unique index to `mail_type.code` column and add unique validation to `ListFormFactory`. remp/remp#919

### [Sso]

- Upgraded to Laravel 7. remp/remp#491

## [0.24.0] - 2021-05-21

### [Beam]

- Added `TagCategory` filter option to `/top` APIs. remp/remp#898
- Fixed issue with article scroll progress tracking if `article.elementFn` callback wasn't set or didn't return any element.
- Upgraded to Laravel 6. remp/remp#491
- Added UI for `TagCategory`. remp/remp#898
- Fixed filter bugs in article datatable. remp/remp#921
- Fixed fulltext search bug in datatables. remp/remp#923

### [Campaign]

- **BREAKING**: Moved key used for segment caching from `CacheSegmentJob` into `SegmentAggregator`. remp/crm#1765
  - If you use `CacheSegmentJob::key()`, replace it with `SegmentAggregator::cacheKey($campaignSegment)`.
- Added API to temporary override user's presence in cached segment (next scheduled cache job loads list against segment query). remp/crm#1765
- Changed the format of stored tracking parameters in cookies and local storage. remp/remp#889
- Added option to set timeout for Beam and Pythia segments. remp/remp#899
- Removed API's throttle (rate limiting). APIs `SegmentCacheController@addUserToCache` and `SegmentCacheController@removeUserFromCache` have to receive more requests than predefined limit. Will be enabled back with Laravel 8. remp/remp#913
- Upgraded to Laravel 6. remp/remp#491

### [Mailer]

- Converted database to UTF8mb4 encoding. Migration can take some time according to the volume of data in the database. remp/remp#895
- Removed unused table `hermes_tasks_old` created as backup when Hermes was updated to v2.1 _(see `HermesRetry` migration; commit [5fcd07ff](https://github.com/remp2020/remp/commit/5fcd07ffdda658334b0b990252eb94af0857b894))_.
- Added mail job stats updating to `MailgunEventHandler`. Every suitable Mailgun event is processed and corresponding column in `mail_job_batch_templates` updated. remp/remp#853
- Added `only-converted` option to `ProcessJobStatsCommand` to run command to update only `converted` column in `mail_job_batch_templates` table. remp/remp#853
  - If you have `mail:job-stats` command in your scheduler, it should be enough to run it just once a day.
  - Please add new entry to your scheduler with `mail:job-stats --only-converted` based on how often you want your conversion stats to be updated. We recommend every 10 minutes. Rest of the stats should now be updated continuously immediately when Mailer receives Mailgun webhook.

### [Sso]

- Upgraded to Laravel 6. remp/remp#491

## [0.23.0] - 2021-05-12

### Project

- Fixed possible UI flaws caused by select pickers overflowing if the content is too wide. remp/remp#781

### [Beam]

- **BREAKING**: Environment variable `QUEUE_DRIVER` changed to `QUEUE_CONNECTION`, please update your `.env` file accordingly. remp/remp#491
- Upgraded Laravel version to 5.8. remp/remp#491
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
- Added endpoint for conversion sankey diagram data retrieval. remp/remp#551
- Added sankey d3 plugin node package. remp/remp#551
- Added sankey diagrams into `user path` section. Currently we are tracking 2 types of conversion sources (first/last visit before conversion) therefore there are 2 sankey diagrams. remp/remp#551

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

[0.34.1]: https://github.com/remp2020/remp/compare/0.34.0...0.34.1
[0.34.0]: https://github.com/remp2020/remp/compare/0.33.0...0.34.0
[0.33.0]: https://github.com/remp2020/remp/compare/0.32.2...0.33.0
[0.32.2]: https://github.com/remp2020/remp/compare/0.32.1...0.32.2
[0.32.1]: https://github.com/remp2020/remp/compare/0.31.0...0.32.1
[0.31.0]: https://github.com/remp2020/remp/compare/0.30.2...0.31.0
[0.30.2]: https://github.com/remp2020/remp/compare/0.30.1...0.30.2
[0.30.1]: https://github.com/remp2020/remp/compare/0.30.0...0.30.1
[0.30.0]: https://github.com/remp2020/remp/compare/0.29.0...0.30.0
[0.29.0]: https://github.com/remp2020/remp/compare/0.28.0...0.29.0
[0.28.0]: https://github.com/remp2020/remp/compare/0.27.1...0.28.0
[0.27.1]: https://github.com/remp2020/remp/compare/0.27.0...0.27.1
[0.27.0]: https://github.com/remp2020/remp/compare/0.26.0...0.27.0
[0.26.0]: https://github.com/remp2020/remp/compare/0.25.1...0.26.0
[0.25.1]: https://github.com/remp2020/remp/compare/0.25.0...0.25.1
[0.25.0]: https://github.com/remp2020/remp/compare/0.24.0...0.25.0
[0.24.0]: https://github.com/remp2020/remp/compare/0.23.0...0.24.0
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
[0.9.1]: https://github.com/remp2020/remp/compare/0.9.0...0.9.1
[0.9.0]: https://github.com/remp2020/remp/compare/0.8.2...0.9.0
[0.8.2]: https://github.com/remp2020/remp/compare/0.8.1...0.8.2
[0.8.1]: https://github.com/remp2020/remp/compare/0.8.0...0.8.1
[0.8.0]: https://github.com/remp2020/remp/compare/0.7.0...0.8.0