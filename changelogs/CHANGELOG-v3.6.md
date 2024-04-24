## [3.6] - 2024-04-24

### [Beam]

- **BREAKING**: Removed unused `autoload` property within `Config`.  remp/remp#992
  - If you're loading configs yourself and actively using `autoload` property, you need to remove dependency on it before updating to this version.
- **DEPRECATED**: Deprecated usage of `ConversionRateConfig` from DI container or creating directly through constructor. Use `ConversionRateConfig::build()` method instead. remp/remp#992
  - Remember, by using `ConversionRateConfig::build()` you'll get newer values from the config and not cached ones.
- **DEPRECATED**: Deprecated `Article::getConversionRateConfig()`. Create your own instance of ConversionRateConfig instead. remp/remp#992
- **DEPRECATED**: Deprecated usage of `Article::getConversionRateAttribute` without passing ConversionRateConfig as a first parameter. remp/remp#992
- Fixed issue with `load_timespent` parameter in `/journal/pageviews/list` which didn't include the timespent into pageview object. remp/remp#1334
- Added average spent times into article detail page. remp/remp#1328
- Added parameter to pass `ConversionRateConfig` as a first parameter to `Article::getConversionRateAttribute()`. remp/remp#992
- Added `ConversionRateConfig::build()` to create new instance of `ConversionRateConfig` with fresh values from the config. remp/remp#992
- Added ability to cache values for 60 seconds within `Article::getConversionRateConfig()` for long-running processes/workers. remp/remp#992
- Fixed slow load of datatables for systems with high amount of authors and tags. remp/remp#1347

### [Campaign]

- Fixed typo in event name for Newsletter banner in README.
- Fixed loading of available countries for campaign copy action. remp/remp#1323
- Fixed the paging of scheduled campaigns. remp/remp#1310
- Refactored referer filter to traffic source filter. remp/remp#1336
- Added ability to filter campaign by session referer (traffic source) in showtime. remp/remp#1336

### [Mailer]

- **IMPORTANT**: The default Hermes queue for asynchronous events is now `hermes_tasks` (medium priority) instead of `hermes_tasks_low` (low priority). remp/remp#1342
  - If you emit your own Hermes events with the default priority, please revise whether they should keep using the default priority, or whether they should be explicitly emitted as "low priority".
- **DEPRECATED**: Deprecated `autoload` flag within configs. From now on, all configs are loaded regardless of this flag and this flag will be removed in the next major release. remp/remp#992
  - Consequently, we deprecated method `ConfigsRepository::loadAllAutoload()`. Use `ConfigsRepository::all()` instead.
- **DEPRECATED**: Deprecated `MailgunMailer::mailer()`. Use `MailgunMailer::createMailer()` instead. remp/remp#992
- Fixed incorrect `/mailer/health` healthcheck HTTP status code in case of failure (was always 200). remp/remp#1322
- Fixed conditions to unreachable healthcheck messages. remp/remp#1322
- Added new parameters between default template parameters to identify newsletter (`newsletter_id`, `newsletter_code`, `newsletter_title`) and variant (`variant_id`, `variant_code`, `variant_title`). remp/remp#1321
- Added support for One-Click unsubscribe according to RFC8058. [remp2020/mailer-module#3](https://github.com/remp2020/mailer-module/pull/3)
- Added option to configure maximum number of send attempts in `SendEmailHandler`. remp/remp#1331
  - You can configure this in your `config.local.neon` by calling e.g. `setMaxRetries(10)` within `setup` directive of `sendEmailHermesHandler` service.
- Fixed issue with oversize images in MS Outlook. remp/remp#1330
- Fixed issue with persistent embed cookies stored in system `tmp` folder, which were shared across releases. remp/helpdesk#2587
  - Each release now stores embed cookies in its own temp folder.
- Added ability to set custom CURL settings for `EmbedParser`. remp/helpdesk#2594
- Fixed buggy regexp pattern in `NewsfilterGenerator` causing elements to be removed non-voluntary. remp/crm#3151
- Added ability to refresh internal config cache after a certain amount of time mainly for a longer running processes/workers. remp/remp#992
- Added ability for `MailgunMailer` and `SmtpMailer` to refresh config during the runtime (for example when there's long-running worker and the config is changed). remp/remp#992
- Added new events emitted before (`Remp\MailerModule\Events\BeforeUserEmailChangeEvent`) and after (`Remp\MailerModule\Events\UserEmailChangedEvent`) email change. remp/remp#1348

---

[3.6]: https://github.com/remp2020/remp/compare/3.5.0...3.6.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
