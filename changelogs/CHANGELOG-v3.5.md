## [3.5] - 2024-01-23

### Project

- Fixed possible redirect issue after login causing HTTP 404 after successful login. remp/remp#1235

### [Beam]

- Fixed entity and segment group seeder to avoid duplicates in the database. remp/remp#1317
- Added `--force` parameter to migration in `update-prod` make target. remp/remp#1317

### [Campaign]

- Changed campaign display rules evaluation to evaluate pageview attributes before including campaign between active campaigns. remp/remp#1302
- Fixed bug - when copying campaign, removal of assigned segment affected existing campaign segments. remp/remp#1308
- Fixed reporting unknown country as exception. remp/remp#1314
- Added `--force` parameter to migration in `update-prod` make target. remp/remp#1317

### [Mailer]

- **BREAKING**: Updated `monolog/monolog` to version `^3.0`. remp/remp#1315
  - If any of your extensions rely on monolog, please review your implementation.
- **BREAKING**: Updated `robmorgan/phinx` library to the latest version. remp/remp#1315
  - If you write your own migrations, you might want to test them against an empty DB. Types are now strict and older migrations could be broken if you used incorrect type in the past.
- **BREAKING**: Updated `latte/latte` templating system to version `^3.0`. remp/remp#1315
  - If you create your own presenters/templates, please see the migration guide at https://latte.nette.org/en/cookbook/migration-from-latte2.
- **BREAKING**: Updated `nette/mail` library to version `^4.0`. remp/remp#1315
  - If you use it directly or extend our `SmtpMailer`, please review breaking changes from https://github.com/nette/mail/releases/tag/v4.0.0.
- **IMPORTANT**: Updated Nette's underlying libraries to version `^4.0` (`nette/robot-loader`, `nette/utils`). remp/remp#1315
- **IMPORTANT**: Updated `mailgun/mailgun-php` to version `^4.0`. remp/remp#1315
- Fixed possible render time / memory issues on job detail belonging to newsletter list with lots of emails.
- Added support for the new object types into parser used by R5M mail generator. remp/web#2312
- Added setup methods into R5M related generator to allow work with another layout. remp/novydenik#1184
- Changed generator rule for `<em>` HTML tag - removed new line. remp/crm#3012
- Changed command order in `update-dev` and `update-prod` make targets to clear cache before running other command. remp/remp#1317

### [Sso]

- Added `--force` parameter to migration in `update-prod` make target. remp/remp#1317

---

[3.5]: https://github.com/remp2020/remp/compare/3.4.0...3.5.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
