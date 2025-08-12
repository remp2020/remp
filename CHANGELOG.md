# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- **DEPRECATED**: Commands for rollovers and data retention are being sunset in favor of Elasticsearch's ILM. remp/remp#1419
    - Configure your indices to use ILM policies, see base init script for Docker image [here](https://github.com/remp2020/remp/blob/master/Docker/elasticsearch/create-indexes.sh).
- Updated init script for Elasticsearch Docker image to use Index Lifecycle Management (ILM). remp/remp#1419
- Added parameters `published_from` and `published_to` into API call `/api/v2/articles/top` to filter returned articles by `published_at` datetime. remp/respekt#441
- Added parameters `article_published_from` and `article_published_to` into API call `/api/conversions` to filter returned conversions by article's `published_at` datetime. remp/respekt#441
- Fixed conversion filtering in the Articles - Conversions section; the sum and average fields always worked with all article conversions and ignored the time-based filter. remp/remp#1431

### [Campaign]

- Optimized size of `showtime.php` response by trimming unused snippets where possible. remp/remp#1428
- Fixed HTML overlay banner not hiding overlay when the banner is closed. remp/remp#1424
- Fixed call on undefined object index during banner's closing remp/helpdesk#3773

### [Mailer]

- Fixed mail resend when Mailgun throws RuntimeException. remp/remp#1427

## Archive

- [v4.1](./changelogs/CHANGELOG-v4.1.md)
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
