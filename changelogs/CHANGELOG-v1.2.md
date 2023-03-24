## [1.2.0] - 2023-02-23

### [Mailer]

- **BREAKING**: Added explicit types to `RedisClientTrait`.
    - If you use the trait in your own extensions, you might encounter type incompatibility issues of your constructors and class properties. Make the necessary changes based on the error messages.
- **IMPORTANT**: Changed primary key from `int` to `bigint` for `mail_user_subscriptions` table. remp/remp#1187
    - This migration is a two-step process that requires your manual action - running `mail:migrate-user-subscriptions-and-variants` in the off-peak hours. Since some tables are very exposed and cannot be locked for more than a couple of seconds, we decided to migrate the data into the new table manually and keep the old and new table in sync. Based on the amount of your data, the migration can take hours.
- Added the soft delete of mail type variants. remp/crm#2721
- Added ability to log apple bots use in Mailgun "opened" events via standalone Hermes handler (disabled by default). remp/analytika#137
- Added `TrackSubscribeUnsubscribeHandler` hermes handler, which sends event to Tracker after user subscribes/unsubscribes from mail type. remp/remp#1226
- Added ability to track RTM parameters in the `/api/v1/users/subscribe` API. remp/remp#1237
- Added support for standalone HTTP webhook signing key. remp/remp#1232
    - Mailgun used to use domains API key to sign the requests, however it currently is a separate signing key.
- Added filter by `user_id` support to mailer `LogsHandler`. remp/remp#1188

### [Campaign]

- Fixed increasing pageviews for campaigns which banners were not displayed due to the priority rules for banners on the same position. remp/remp#1213
- Added syntax highlighting to Variables section. remp/remp#1214
- Added support for new banner rules in campaign. Now you can determine if campaign should display after user clicked or closed the banner. remp/remp#960
- Added storing of collapse state for collapsible banner. remp/remp#960
    - If user collapses campaign banner then it displays collapsed on the next display.
    - In collapsible banner settings there is a new toggle to override this behaviour and display banner always in initial state.

---

[1.2.0]: https://github.com/remp2020/remp/compare/1.1.0...1.2.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker