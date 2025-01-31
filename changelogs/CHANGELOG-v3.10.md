## [3.10] - 2024-10-25

### [Beam]

- [Tracker] Fixed Tracker not publishing messages to pub/sub due to prematurely closed client. remp/remp#1384
- [Segments] Fixed Elasticsearch 8 incompatibility in mapping caching. remp/remp#1385
- Fixed newsletters being marked as `finished` in some occurences. remp/respekt#289
- Fixed dependency issue not allowing to apply security hotfixes to some affected libraries.

### [Campaign]

- Fixed missing session source on showtime requests which got executed before the session source could be stored. remp/web#2656
- Fixed campaign-module migrations by moving country seeder into campaign-module. remp/remp#1287
- Added option to use Campaign and Banner UUIDs in the search box.
- Fixed ARIA-compatibility of close buttons in Campaign banners. remp/helpdesk#3037
- Fixed dependency issue not allowing to apply security hotfixes to some affected libraries.

### [Mailer]

- Added ability to set custom health check TTL for `ProcessJobCommand`. remp/remp#1376
- Fixed parsing of attachment's filename from header within `MailgunMailer`. remp/remp#1386
  - Previous implementation incorrectly parsed filenames with dash. Filename of attached file "invoice-2024-09-24.pdf" would be only last part "24.pdf".
  - Added `MailHeaderTrait` with method `getHeaderParameter()` and tests to validate it.
- Fixed mail type stats when groupped by week or month. remp/remp#1374
- Changed behavior of `rtm_click` parameter. If the mail template disables click tracking, `rtm_click` is not added to the links anymore. remp/respekt#305
- Fixed removed `<a>` by the newsfilter replace rules which didn't expect anchors without `href` attribute. remp/helpdesk#3082
- Fixed possible issues with chart rendering if there's no data to evaluate yet.

---

[3.10]: https://github.com/remp2020/remp/compare/3.9.0...3.10.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
