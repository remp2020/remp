# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Fixed scopeMostReadByPageviews causing newsletter criterion use timespent sum instead of pageviews count.
- Fixed default value configuration for public dashboard authentication.

### [Campaign]

- Fixed ARIA-compatibility of all campaign banners. remp/remp#1368
- Fixed an unclickable Overlay Rectangle banner when no main text and button text is present. remp/remp#1393
- Changed path to maxmind database in .env.example. remp/remp#1402
- Refactored banner color schemes to banners config file. remp/remp#1379
- Fixed color contrasts on campaign banners. remp/remp#1379

### [Mailer]

- Changed Sender condition to include HTML/text version of email based on the generated content and not mail template content. remp/remp#1392
- Added `created_at` to the mail subscription objects in `/api/v1/users/user-preferences` API. remp/respekt#301
- Fixed newsletter list seeder. remp/remp#1391
- Added `FrontendPresenter` for identification of presenters available to public. remp/remp#1395
- Added `update()` (to updated `updated_at`) methods to `BatchesRepository`, `JobsRepository`, `LogsRepository`, `SourceTemplatesRepository`. remp/remp#1397
- Fixed missing parameter exception in Error4xxPresenter. remp/remp#1399
- Changed data retention logic to remove data in batches. remp/remp#1383
- Added option to disable Apple bot check in `UnsubscribeInactiveUsersCommand`. remp/remp#1396

## Archive

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

[Unreleased]: https://github.com/remp2020/remp/compare/3.2.0...master
