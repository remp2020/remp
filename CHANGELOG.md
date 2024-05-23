# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Fixed newsletters not being sent anymore if there was an issue with sending for more than two sending periods. remp/remp#1351
- [Tracker] Changed Goa library from v1 to v3. remp/remp#1341
- [Segments] Changed Goa library from v1 to v3. remp/remp#1341
- [Segments] Added `load_progress` attribute to journal list pageviews. remp/remp#1335

### [Mailer]

- **BREAKING**: Removed `autoload` flag from configs table and `ConfigsRepository::loadAllAutoload`.
- **BREAKING**: Removed command `mail:remove-old-batches`. remp/remp#1354
  - Use newly added `application:cleanup` instead.
- **BREAKING**: Fixed `RedisClientTrait` default database value from `0` to `null`. remp/remp#1357
  - This affects you only if you configure Redis database manually to something other than `0` in your config.  
  - The change fixes use of `RedisClientTrait` ignoring database configured in `RedisClientFactory` and forcing DB `0` instead, causing your data to be stored in DB `0` even if you configured your environment/`RedisClientFactory` to use other database.
  - **IMPORTANT**: It is important to move hermes keys `hermes_tasks_high`, `hermes_tasks`, `hermes_tasks_low` (with `MOVE` command) to correct Redis DB after you shut down your old Hermes workers, and before you start the new ones.
  - **IMPORTANT**: We also recommend not having any active jobs (processing or sending) during the release process. 
- Added option to track variant subscriptions to Tracker. remp/web#2404
- Added Mailer's segment "Everyone" which lists all subscribers known to Mailer. remp/crm#2973
  - This segment should ideally replace `all_users` provided by CRM and effectively serve as a default. Mailer still filters users based on their newsletter subscription to the email they're receiving.
- URL parser generator's segment is now optional. remp/crm#2973
  - If not provided, Mailer's segment with subscribers of selected mail type is used as a default.
- Fixed duplicate entry error when subscribing to already subscribed variant within `UserSubscriptionsRepository->subscribeUser`. remp/remp#1355
- Added `application:cleanup` command to execute configured data retention policies. remp/remp#1354
  - By default, the system purges all expired autologin tokens and processed batches not sent within 24 hours.
  - You can configure/change the retention polices in `config.neon`, see README for more information.
- Fixed status set to batch by `ProcessJobCommand` after processing failed. Batch is now set to original status. remp/remp#1360

## Archive

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
