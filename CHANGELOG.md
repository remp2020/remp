# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281

### [Campaign]

- Changed CSS for collapsible bar banner to fix collisions with iPhone system button. remp/remp#1280
- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281

### [Mailer]

- Fixed date filtering on the newsletter stats page. remp/remp#1231
- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281
- Added hermes handler to notify CRM about refreshing user's data. You can enable the feature in `config.local.neon` (see example file for reference). remp/web#2061
- Fixed typo in the `package.json` definition for `moment-timezone` causing Yarn3 installation issues. [remp2020/mailer-module#2](https://github.com/remp2020/mailer-module/pull/2) 

### [Sso]

- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281

## Archive

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

[Unreleased]: https://github.com/remp2020/remp/compare/3.0.0...master
