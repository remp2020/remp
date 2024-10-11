# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- [Tracker] Fixed Tracker not publishing messages to pub/sub due to prematurely closed client. remp/remp#1384
- [Segments] Fixed Elasticsearch 8 incompatibility in mapping caching. remp/remp#1385
- Fixed newsletters being marked as `finished` in some occurences. remp/respekt#289

### [Campaign]

- Fixed missing session source on showtime requests which got executed before the session source could be stored. remp/web#2656
- Fixed campaign-module migrations by moving country seeder into campaign-module. remp/remp#1287
- Added option to use Campaign and Banner UUIDs in the search box.
- Fixed ARIA-compatibility of close buttons in Campaign banners. remp/helpdesk#3037 

### [Mailer]

- Added ability to set custom health check TTL for `ProcessJobCommand`. remp/remp#1376
- Fixed parsing of attachment's filename from header within `MailgunMailer`. remp/remp#1386
  - Previous implementation incorrectly parsed filenames with dash. Filename of attached file "invoice-2024-09-24.pdf" would be only last part "24.pdf".
  - Added `MailHeaderTrait` with method `getHeaderParameter()` and tests to validate it.
- Fixed mail type stats when groupped by week or month. remp/remp#1374
- Changed behavior of `rtm_click` parameter. If the mail template disables click tracking, `rtm_click` is not added to the links anymore. remp/respekt#305

## Archive

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

[Unreleased]: https://github.com/remp2020/remp/compare/3.2.0...master
