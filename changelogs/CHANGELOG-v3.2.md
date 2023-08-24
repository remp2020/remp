## [3.2] - 2023-08-24

### Project

- Removed `dockerize` from Dockerfiles across the project. It's been replaced by native Docker Compose healthcheck feature.
- Added Elasticsearch and Telegraf configs directly to their respective Docker images, so there's a default config if one is not provided via volume.
- Fixed issue with `yarn link` not being able to link JS packages due to version conflict. remp/remp#1293
- Added explicit `packageManager` definition into `package.json` files so Yarn v3 doesn't complain about outdated lockfile. remp/remp#1294

### [Beam]

- Fixed schedules not being registered for Skeleton application. remp/remp#1292
- Fixed commands not being available to execute for synchronous web event handlers. remp/remp#1292
- Refactored system settings configuration so they're available within Beam module and for Skeleton apps. remp/remp#1292

### [Campaign]

- Changed `asset()` function in `showtime.php` to return absolute URL. remp/remp#1282

### [Mailer]

- Removed `HermesException` for missing `mail_sender_id` from `ValidateCrmEmailHandler`. remp/remp#1291
  - This is valid state. It was introduced as fix by commit https://github.com/remp2020/remp/commit/c2d55b5d7d56f7ba29e3977f33785d77b3ca145a
- Added new `Mailer` segment provider which provides segments of users subscribed to mail types. remp/mnt#114
- Added check for `clicked_at` to `mail:unsubscribe-inactive-users` at all times. remp/novydenik#1115

---

[3.2]: https://github.com/remp2020/remp/compare/3.1.0...3.2.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
