# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Campaign]

- Fixed typo in event name for Newsletter banner in README.
- Fixed loading of available countries for campaign copy action. remp/remp#1323

### [Mailer]

- Fixed incorrect `/mailer/health` healthcheck HTTP status code in case of failure (was always 200). remp/remp#1322
- Fixed conditions to unreachable healthcheck messages. remp/remp#1322
- Added new parameters between default template parameters to identify newsletter (`newsletter_id`, `newsletter_code`, `newsletter_title`) and variant (`variant_id`, `variant_code`, `variant_title`). remp/remp#1321
- Added support for One-Click unsubscribe according to RFC8058. [remp2020/mailer-module#3](https://github.com/remp2020/mailer-module/pull/3)
- Added option to configure maximum number of send attempts in `SendEmailHandler`. remp/remp#1331
  - You can configure this in your `config.local.neon` by calling e.g. `setMaxRetries(10)` within `setup` directive of `sendEmailHermesHandler` service. 
- Fixed issue with oversize images in MS Outlook. remp/remp#1330

## Archive

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
