# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Fixed sorting issues in datatables. remp/remp#1409
- Added impression tracking feature. remp/remp#1228

### [Campaign]

- Fixed cast string to boolean in form inputs before validation in `CampaignRequest::prepareForValidation`. remp/crm#3472
- Added `data-href` attribute with target URL of banner to banners which lost this attribute during accessibility-related changes.
- Changed HTML banner rendering for JS-based banners; the HTML is not being rendered anymore to avoid accessibility warnings.
- Added information about which campaigns (including variants and banners) were displayed to the Campaign Debugger. remp/helpdesk#3591
- Added referal property check into Showtime. remp/remp#1290
- Updated chart library to the latest version. remp/remp#1287

### [Mailer]

- Added generator template validation. remp/remp#1398
- Added support for the processing of additional elements in `RespektContent`. remp/respekt#388
- Added HTML WYSIWYG editor for URL Parser email generator intro and footer inputs. remp/remp#1416
- Added extra index to improve performance of dashboard loading. remp/remp#1418
- Added setup method for `EmbedParser` to preprocessing of thumbnail image. remp/remp#1411

## Archive

- [v4.0](./changelogs/CHANGELOG-v4.0.md)
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

[Unreleased]: https://github.com/remp2020/remp/compare/4.0.0...master
