# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### Project

- Removed unused `chart.js` and `vuefilters.js` from the shared `remp/js-commons` library.
- Fixed `DateTimePickerWrapper` to support Vue v3.

### [Beam]

- Removed reference to `vuefilters` from the shared `remp/js-commons`, they weren't activelly used anywhere.

### [Campaign]

- Fixed chart.js configuration objects to match the current library version.
- Added IP address targeting for campaigns with whitelist/blacklist support for single IPs and ranges. remp/euobserver#132
- Moved `vuefilters` internally from the shared library inside the project.
- Fixed JS errors and possibly broken click event on the banner edit page. [remp2020/remp#236](https://github.com/remp2020/remp/pull/236)

### [Mailer]

- **IMPORTANT**: Changed column `user_id` to nullable in `mail_user_subscriptions` table (migration included).
    - This migration is non-blocking but it may take some time - e.g locally, 30M entries takes about 5 minutes.
- **BREAKING**: Added new required env variable `APP_URL` — base URL of the Mailer admin. remp/remp#1516
  - Make sure this variable is set, it's required for native unsubscribe links. 
- **BREAKING**: Added support for "external" mail types — newsletters that can be sent to subscribers without user account in CRM. remp/remp#1516
    - Added `is_external` boolean column to `mail_types` table (migration included).
    - External mail types skip segment selection when creating/editing mail jobs — recipients are resolved directly from subscriptions.
- **BREAKING**: Made `user_id` optional in subscribe/unsubscribe/is-subscribed/is-unsubscribed/bulk-subscribe/user-preferences API endpoints. remp/remp#1516
    - For non-external mail types, `user_id` is still required and validated at the handler level.
    - For external mail types, `user_id` can be omitted.
- **BREAKING** - Changed `UserSubscriptionsRepository::allSubscribers()` to `allSubscribersWithUserId()`, filtering out subscribers without `user_id` from segment results. remp/remp#1516
- Added `is_external` field to mail type listing API responses (v1, v2, v3). remp/remp#1516
- Added `is_external` field to mail type upsert API response and creation. remp/remp#1516
- Added `is_external` checkbox to the mail type (newsletter list) create/edit form. remp/remp#1516
- Fixed `mail_types.code` index to be unique for MySQL 8.4+ compatibility.
  - The foreign key from `mail_user_preferences.code` requires the referenced column to have a unique index.
- Added a priority parameter to the content generator replacers register method to ensure the replacers are applied in the correct order. remp/remp#1450
- Added AnchorWirelinkReplace content generator replacer to support deeplinking in emails. remp/remp#1450
- Added admin UI for managing mail type variants (`ListVariantPresenter`) — list, create, edit, show, and soft-delete variants from the newsletter list detail page. remp/remp#1516
- Added ability to mark a mail type as multi-variant from the newsletter list form (`is_multi_variant` checkbox). remp/remp#1516
- Added `SubscribersImporter` model for bulk-subscribing emails to a mail type, with optional pruning of subscribers missing from the imported list. remp/remp#1516
- Added "Import subscribers" admin flow for external mail types (`ListSubscribersImportFormFactory`) — supports selecting target variants, forcing no-variant subscription, and removing missing subscribers. remp/remp#1516
- Added "Import subscribers" admin flow per variant (`VariantSubscribersImportFormFactory`) for external mail types. remp/remp#1516
- Added `MailSettingsPresenter` with public `unSubscribeEmail`, `unSubscribeSuccess`, and `tokenExpired` routes — native Mailer-side unsubscribe pages that don't require a CRM round-trip. remp/remp#1516
- Added `mailer_unsubscribe` template variable available in all emails when an autologin token is present, linking to the new native (directly in Mailer) unsubscribe page. remp/remp#1516
- Added `actionButtons` table setting to the shared `DataTable` component — renders custom action buttons (URL + label) in the data table toolbar. remp/remp#1516
- Fixed autologin token handling in the unsubscribe flow. remp/remp#1516
- Fixed `ListVariantsRepository` to order variants by count correctly. remp/remp#1516
- Fixed `RulesTrait` not formatting `[caption]` tags in generator with its designated template but rather with `$imageTemplate`. remp/euobserver#181
- Fixed temporary duplication of Mailgun webhook events in the sending summary widget. remp/euobserver#162
    - Subsequent events coming from Mailgun weren't previously filtered and always incremented stat counters. Multiple opens/clicks in the email caused repeated incrementation of a metric.
    - The stats were always recalculated and corrected by `mail:job-stats` command, which should be run at least daily.

## Archive

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

[Unreleased]: https://github.com/remp2020/remp/compare/4.3.0...master
