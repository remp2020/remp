# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Lowered z-index of the iota on-site overlay to 9996–9998 so it no longer covers Campaign banners (9999 and above). remp/helpdesk#5008

### [Campaign]

- Added `From` option to campaign `Every N page views` display rule, allowing the banner to start displaying at a later pageview than the first one. remp/remp#1488
- Fixed overlay banners taller than the viewport (e.g. phone in landscape) being cut off and unclosable. The backdrop now scrolls. remp/helpdesk#5008
- Fixed banners with a publisher-defined color scheme (`config/banners.local.php`) failing to render after a deploy, because `campaigns:refresh-cache` ran without the local config and overwrote the cached schemes with the defaults. An unknown scheme now fails with an error naming the banner, the scheme and the available schemes, and saving a banner or campaign in admin re-serializes the config maps to Redis. remp/helpdesk#5008

### [Mailer]

- **BREAKING**: Removed `JsonLDContent::postProcessMeta()` in favor of the narrower `processImage(?string): ?string` and `processAuthors(array): array` hooks, which `JsonLDContent` applies before constructing `Meta`. remp/remp#1499
- Added audio metadata parsing to `JsonLDContent`. remp/remp#1499
- `JsonLDContent` now also accepts a plain URL string in the schema's `image` property. Previously only an `ImageObject` (or an array of them) was read.
- Refactored X embedding from `Euobserver\EmbedParser` to the shared `Remp\Mailer\Models\Generators\EmbedParser::fetchXPreviewImage()`. remp/remp#1505

## Archive

- [v5.1](./changelogs/CHANGELOG-v5.2.md)
- [v5.1](./changelogs/CHANGELOG-v5.1.md)
- [v5.0](./changelogs/CHANGELOG-v5.0.md)
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

[Unreleased]: https://github.com/remp2020/remp/compare/5.1.0...master
