# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### Project

- Fixed possible redirect issue after login causing HTTP 404 after successful login. remp/remp#1235

### [Campaign]

- Fixed search by name on snippets listing. remp/remp#1303
- Added snippet search to the universal search bar. remp/remp#1303
- Fixed Campaign's `showtime.php` crashing if there are no active campaigns.
- Added campaign collections. remp/remp#1286

### [Mailer]

- **BREAKING**: Removed `EnvironmentConfig::setParam()` and `EnvironmentConfig::getParam()` methods. remp/remp#1299
  - Use of these could lead to circular dependency issues if values were read by environment config itself.
  - We recommend the extraction of these values to their separate config classes.
- Fixed circular dependency issue with configs using environment variables. remp/remp#1299
- Added new parameter `start_at` into `v1/mailer/jobs` and `v2/mailer/jobs` to allow schedule the start of sending. remp/respekt#19
- Added `BeforeUsersDeleteEvent` and `UsersDeletedEvent` events to emit before and after users are deleted. remp/remp#1301

## Archive

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