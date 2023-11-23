## [3.4] - 2023-11-23

### Project

- Fixed possible redirect issue after login causing HTTP 404 after successful login. remp/remp#1235

### [Campaign]

- Fixed search by name on snippets listing. remp/remp#1303
- Added snippet search to the universal search bar. remp/remp#1303
- Fixed Campaign's `showtime.php` crashing if there are no active campaigns.
- Added campaign collections. remp/remp#1286
- Added `ONE_TIME_BANNER_ENABLED` env variable to disable fetching data for one time banners.
- Added `REDIS_PERSISTENT` env variable to enable presistent redis connection.
- Added showtime optimizations for better performance.
- Fixed routes same name conflict.
- Added support for Redis PHP extension.
- Added `REDIS_PARAMETER_LIMIT` env variable to avoid errors when calling Redis functions with large arrays. remp/remp#1307
- Added index to `created_at` and `updated_at` campaign columns. remp/remp#1286
- Fixed sorting campaigns by `is active` column. remp/helpdesk#2231

### [Mailer]

- **BREAKING**: Removed `EnvironmentConfig::setParam()` and `EnvironmentConfig::getParam()` methods. remp/remp#1299
  - Use of these could lead to circular dependency issues if values were read by environment config itself.
  - We recommend the extraction of these values to their separate config classes.
- Fixed circular dependency issue with configs using environment variables. remp/remp#1299
- Added new parameter `start_at` into `v1/mailer/jobs` and `v2/mailer/jobs` to allow schedule the start of sending. remp/respekt#19
- Added `BeforeUsersDeleteEvent` and `UsersDeletedEvent` events to emit before and after users are deleted. remp/remp#1301

### [Sso]

- Fixed scenario when invalidated token was allowed to be refreshed just to be evaluated as invalid again.
- Fixed blacklist-related exception if token was blacklisted but the blacklist was not enabled.

---

[3.4]: https://github.com/remp2020/remp/compare/3.3.0...3.4.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
