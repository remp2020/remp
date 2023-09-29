# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Added gender identification in article title image and added gender balance info to article detail. remp/remp#1274

### [BeamModule]

- Added `article.show.info` widget group placeholder. Implement your own widget using `arrilot/laravel-widgets` package and display it at provided placeholder in the view. remp/remp#1274
- Added JS tracker parameters `canonicalUrl` and `referer`, allowing overriding URL and referer that are being tracked. remp/remp#1297 

### [Campaign]

- Added visual changes for the overlay two buttons banner - minor button texts are now on separate line.
- Added ability to target campaign by user system language. remp/remp#1283
- Added suppressed banners due to prioritization to showtime response for easier debugging. remp/remp#1295

### [Mailer]

- **BREAKING** Changed that `X-Mailer-Template-Params` mail header is no longer sent to `SmtpMailer`, as it may contain sensitive information. remp/remp#1296
  - If this header is still required by your implementation, you need to implement a custom SmtpMailer. 
- Added string error code to the Subscribe APIs to differentiate between different 404 scenarios. remp/web#2263 
- Fix Mailer segment provider users acquiring. Provided segment code needs to be processed before fetching users from database. remp/mnt#114
- Fix New template generator form - broken sorting value `after`. If selected, select box was not shown. remp/helpdesk#2073

## Archive

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
