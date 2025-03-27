# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- **BREAKING**: Changed minimum Go version for building Tracker API and Segments API to 1.23.
- **IMPORTANT**: Removed data of sections "Visitors" and "Google Analytics Reporting", which we removed in the previous version. remp/remp#1349
  - Migration removes tables `session_devices` and `session_referers`.
- Changed banner preview components as now include and run custom JS inside of iframe. remp/crm#3353
- Added option to specify api version for gender balance in env. remp/helpdesk#3303
- Fixed newsletter criteria not always selecting the desired number of articles. remp/respekt#378
- [Segments] Fixed missing mapping of some optional Elasticsearch fields (`subscribed`, `signed_in`, `revenue`). remp/remp#1394
    - Mapping is now pushed explicitly during Segments service startup.
    - Missed mapping may have caused problems when doing group by filter using untracked field.

### [Campaign]

- Refactored showtime caching to replace serialized objects with JSON cache. remp/remp#1401

### [Campaign]

- Added campaign targeting based on operating system. remp/remp#1403 
- Refactored device and operating rules to their own class `DeviceRulesEvaluator`. remp/remp#1403

### [Mailer]

- **BREAKING**: Renamed `Remp\MailerModule\Models\Generators\ArticleLocker` to `Remp\MailerModule\Models\Generators\HtmlArticleLocker`. remp/novydenik#1324
  - If you work with the class directly, please update your includes.
- **BREAKING**: Renamed DI service `articleLocker` to `htmlArticleLocker`. remp/novydenik#1324
  - If you configure the service further, please update your `config.neon` file.
- Updated dependencies to their latest major versions.
- Added the ability to select the 'Subscription welcome email' and 'Unsubscribe goodbye email' option for any email that belongs to the newsletter, in addition to system emails. remp/remp#1388
- Fixed possible memory limit issues on list form factory. remp/remp#1404
- Changed image in `RespektContent` to be optional. remp/respekt#286, remp/respekt#386
- Fixed performance issue of batch processing in the "remove unsubscribed" steps. The altered query should perform better. remp/remp#1407
- Added support for UTM parameters to mail link replace. remp/respekt#377
- Added SnippetArticleLocker which provides way for generators to define lock messages via snippets. remp/novydenik#1324

## Archive

- [v3.11](./changelogs/CHANGELOG-v3.11.md)
- [v3.10](./changelogs/CHANGELOG-v3.10.md)
- [v3.9](./changelogs/CHANGELOG-v3.9.md)
- [v3.8](./changelogs/CHANGELOG-v3.8.md)
- [v3.7](./changelogs/CHANGELOG-v3.7.md)
- [v3.6](./changelogs/CHANGELOG-v3.6.md)
- [v3.5](./changelogs/CHANGELOG-v3.5.md)
- [v3.4](./changelogs/CHANGELOG-v3.4.md)
- [v3.3](./changelogs/CHANGELOG-v3.3.md)
- [v3.2](./changelogs/CHANGELOG-v3.2.md)
- [v3.1](./changelogs/CHANGELOG-v3.1.md)
- [v3.0](./changelogs/CHANGELOG-v3.0.md)
- [v2.2](./changelogs/CHANGELOG-v2.2.md)
- [v2.1](./changelogs/CHANGELOG-v2.1.md)
- [v2.0](./changelogs/CHANGELOG-v2.0.md)
- [v1.2](./changelogs/CHANGELOG-v1.2.md)
- [v1.1](./changelogs/CHANGELOG-v1.1.md)
- [v1.0](./changelogs/CHANGELOG-v1.0.md)
- [v0.*](./changelogs/CHANGELOG-v0.md)

---

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

[Unreleased]: https://github.com/remp2020/remp/compare/3.11.0...master
