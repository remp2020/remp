# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### Project

- Removed `dockerize` from Dockerfiles across the project. It's been replaced by native Docker Compose healthcheck feature.
- Added Elasticsearch and Telegraf configs directly to their respective Docker images, so there's a default config if one is not provided via volume. 

### [Campaign]

- Changed `asset()` function in `showtime.php` to return absolute URL. remp/remp#1282

## Archive

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

[Unreleased]: https://github.com/remp2020/remp/compare/3.1.0...master
