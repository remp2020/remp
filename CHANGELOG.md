# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### Project

- **BREAKING**: Bumped yarn minimal version to 2. Update your yarn installation by command `yarn set version 2.x`. remp/remp#565

### [Beam]

- Added missing `funnelId` parameter to remplib functions for tracking `payment` and `purchase` events. remp/crm#2860
- Fixed build issues of Go applications due to rename of transitive dependency of Goa v1. remp/remp#1275
- **IMPORTANT** - Moved Beam core functionality into extensions folder as the Laravel package. remp/remp#565

### [Campaign]

- Added copy banner link to banner detail and banner edit form. remp/remp#1259

### [Mailer]

- **IMPORTANT**: Removed hardcoded "memory_limit" configuration (to "256M") in the `mail:process-job` command.
  - If you encounter memory limit issues with the command, configure the memory limit yourself either for the whole instance, or for this single command by using `php -d memory_limit=256M bin/command.php` option. 
- **IMPORTANT**: Added `mail:bigint_migration_cleanup` command, which drops left-over tables, after migration to bigint for `mail_user_subscriptions`, `mail_user_subscription_variants`, `autologin_tokens`, `mail_log_conversions`, `mail_logs` tables. remp/crm#2591
  - It's recommended to run this command at least 2 weeks after migrating (to preserve backup data, if some issue emerges) after successful migration to drop left-over tables.
- Removed deleted mail types from dashboard stats. remp/remp#1269
- Added support for select boxes to `ConfigFormFactory`. remp/remp#1271
- Added support for horizontal scroll in DataTable (parameter `scrollX` in table settings). remp/remp#1270
- Fixed mail click tracker to not modify URL in any way other than adding required query parameter. remp/remp#1270
- Changed `url` column type in `mail_template_links` table from `string` to `text` to support longer URLs. remp/remp#1270
- Added Makefile target `make install` to run all commands required after new code is pulled.
- Fixed "Mail click tracker" config. The default setting is now "disabled". remp/remp#1102
- Updated version of `@remp/js-commons` to 2.2 (contains fix for master search issue). remp/remp#1265
- Fixed missing index for `mail_templates.created_at` (column is used by background queries in `TemplatePresenter->renderDefaultJsonData()`). remp/remp#1272
- Fixed duplicate entry error of `hash` in `MailTemplateLinksRepository::add()` function by using `INSERT IGNORE` SQL statement. The error occurred when inserting the same data in a short time. remp/remp#1273
- Fixed speed of listing pages. We changed how we get total row count (`Repository::totalCount()`) from `COUNT(*)` to `COUNT(DISTINCT({$primary}))`. Using DISTINCT with indexed column forces MySQL to use index. remp/remp#1272
- Fixed issue in `/api/v1/users/user-preferences` API which could include deleted mail types in the response. remp/crm#2883
- Added validation for restricting the use of the same email variant in `NewBatchFromFactory` and `NewTemplateFormFactory`. remp/remp#1230
- Added configurable batch size to the `worker:mail` command; use `--batch-size=NUMBER` to set your own batch size. remp/remp#1238 

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
