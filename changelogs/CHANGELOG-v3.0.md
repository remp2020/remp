## [3.0] - 2023-06-28

### Project

- **BREAKING**: Bumped yarn minimal version to 2. Update your yarn installation by command `yarn policies set-version 2.4.3` (replace the version with the latest v2 version). remp/remp#565

### [Beam]

- **IMPORTANT** - Moved Beam core functionality into extensions folder as the Laravel package. remp/remp#565
- Added missing `funnelId` parameter to remplib functions for tracking `payment` and `purchase` events. remp/crm#2860
- Fixed build issues of Go applications due to rename of transitive dependency of Goa v1. remp/remp#1275

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
- Fixed display issues of link stats table to display url in max. 3 lines and forbid horizontal scroll in table. remp/remp#1278
- Added flag `force_no_variant_subscription` to `/api/v1/users/subscribe` and `/api/v1/users/bulk-subscribe` APIs enabling client to prevent _default variant_ subscribe behavior (changed in the v2.2). remp/remp#1279 

---

[3.0]: https://github.com/remp2020/remp/compare/2.2.0...3.0.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

