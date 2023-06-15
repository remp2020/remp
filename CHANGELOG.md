# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Added missing `funnelId` parameter to remplib functions for tracking `payment` and `purchase` events. remp/crm#2860
- Fixed build issues of Go applications due to rename of transitive dependency of Goa v1. remp/remp#1275

### [Campaign]

- Added copy banner link to banner detail and banner edit form. remp/remp#1259

### [Mailer]

- Removed deleted mail types from dashboard stats. remp/remp#1269
- Added support for select boxes to `ConfigFormFactory`. remp/remp#1271
- Added support for horizontal scroll in DataTable (parameter `scrollX` in table settings). remp/remp#1270
- Fixed mail click tracker to not modify URL in any way other than adding required query parameter. remp/remp#1270
- Changed `url` column type in `mail_template_links` table from `string` to `text` to support longer URLs. remp/remp#1270
- Added Makefile target `make install` to run all commands required after new code is pulled.
- Fixed "Mail click tracker" config. The default setting is now "disabled". remp/remp#1102
- Updated version of `@remp/js-commons` to 2.2 (contains fix for master search issue). remp/remp#1265
- Fixed missing index for `mail_templates.created_at` (column is used by background queries in `TemplatePresenter->renderDefaultJsonData()`). remp/remp#1272

## Archive

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

[Unreleased]: https://github.com/remp2020/remp/compare/2.2.0...master
