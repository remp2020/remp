# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Campaign]

- **IMPORTANT**: Scheduled `newsletter_rectangle_templates.terms` database column to become NOT NULL in next major version. remp/remp#1445
    - Ensure all Newsletter Rectangle banners have terms with at least one HTML link before upgrading.
- **IMPORTANT**: Changed `newsletter_rectangle_templates.terms` database column to NOT NULL. remp/remp#1445
    - Migration automatically sets a default value for any existing NULL or empty terms.
- Changed Newsletter Rectangle banner template to require `terms` field with at least one HTML link. remp/remp#1445
    - Existing banners with empty terms or terms without links will fail validation on save.
- Fixed banner `js_includes` and `css_includes` fields saving `[null]` instead of empty array when no includes are specified. remp/remp#1446
- Added timestamps support to collections with sorting in admin listing. remp/remp#1443

### [Mailer]

- Added index to `mail_job_batch.status` to improve Mailer's workers batch checking performance.
- Added index to `mail_template_stats` to improve Mailer's dashboard aggregation performance.
- Allowed cross-origin requests in MailGeneratorFormFactory (opt-in) to support cross-domain submissions if necessary.

## Archive

- [v4.3](./changelogs/CHANGELOG-v4.3.md)
- [v4.2](./changelogs/CHANGELOG-v4.2.md)
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

[Unreleased]: https://github.com/remp2020/remp/compare/4.2.0...master
