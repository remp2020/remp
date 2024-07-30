# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- **IMPORTANT** Removed section Visitors. remp/remp#1349
  - Related data (tables `session_devices` & `session_referers`) are persisted in DB until the next major update.
  - New data are not processed (command `pageviews:process-sessions` was removed).
- **IMPORTANT** Removed section Google Analytics Reporting. remp/remp#1349
  - Data were loaded from discontinued version of Google Analytics.

### [Mailer]

- Fixed default sender in template form - update it to default of newsletter list when newsletter list is selected. remp/respekt#220
- Added functionality to duplicate newsletter lists with the possibility to copy subscribers. remp/remp#1363
- Set `opened` and `clicked` columns in `TemplatePresenter` template listing, to not orderable in favor of more precise numbers. remp/remp#611
- Fixed description of auto subscribe toggle when editing newsletter list. remp/remp#1366
  - If auto subscribe is enabled:
    - When adding newsletter list, all existing users are subscribed to this new newsletter list (see [\Remp\MailerModule\Hermes\ListCreatedHandler](https://github.com/remp2020/mailer-module/blob/b63effb11421cd3582dc0280e6e5bf293223b3b2/src/Hermes/ListCreatedHandler.php#L48)).
    - When editing newsletter list, only new users will be subscribed to edited newsletter list.

## Archive

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
