## [3.1] - 2023-07-24

### [Beam]

- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281
- Fixed broken settings pages. remp/remp#1284

### [Campaign]

- Changed CSS for collapsible bar banner to fix collisions with iPhone system button. remp/remp#1280
- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281

### [Mailer]

- Fixed date filtering on the newsletter stats page. remp/remp#1231
- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281
- Added hermes handler to notify CRM about refreshing user's data. You can enable the feature in `config.local.neon` (see `config.local.neon.example` file for reference). remp/web#2061
- Fixed typo in the `package.json` definition for `moment-timezone` causing Yarn3 installation issues. [remp2020/mailer-module#2](https://github.com/remp2020/mailer-module/pull/2)
- Added new emit of `user-subscribed-variant` and `user-unsubscribed-variant` hermes event when user subscribe or unsubscribes from the mail type variant. remp/web#2061
- Added `mail:unsubscribe-inactive-users` command to mailer module. remp/remp#1277

### [Sso]

- Added make commands `update-dev` and `update-prod` to update development and production environments respectively after new code is pulled. remp/remp#1281

---

[3.1]: https://github.com/remp2020/remp/compare/3.0.0...3.1.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

