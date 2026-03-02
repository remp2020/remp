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
- Fixed `RulesTrait` not formatting `[caption]` tags in generator with its designated template but rather with `$imageTemplate`. remp/euobserver#181

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
