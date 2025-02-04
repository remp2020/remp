## [3.11.0] - 2025-02-04

### [Beam]

- Fixed scopeMostReadByPageviews causing newsletter criterion use timespent sum instead of pageviews count.
- Fixed default value configuration for public dashboard authentication.

### [Campaign]

- Fixed ARIA-compatibility of all campaign banners. remp/remp#1368
- Fixed an unclickable Overlay Rectangle banner when no main text and button text is present. remp/remp#1393
- Changed path to maxmind database in .env.example. remp/remp#1402
- Refactored banner color schemes to banners config file. remp/remp#1379
- Fixed color contrasts on campaign banners. remp/remp#1379

### [Mailer]

- Changed Sender condition to include HTML/text version of email based on the generated content and not mail template content. remp/remp#1392
- Added `created_at` to the mail subscription objects in `/api/v1/users/user-preferences` API. remp/respekt#301
- Fixed newsletter list seeder. remp/remp#1391
- Added `FrontendPresenter` for identification of presenters available to public. remp/remp#1395
- Added `update()` (to updated `updated_at`) methods to `BatchesRepository`, `JobsRepository`, `LogsRepository`, `SourceTemplatesRepository`. remp/remp#1397
- Fixed missing parameter exception in Error4xxPresenter. remp/remp#1399
- Changed data retention logic to remove data in batches. remp/remp#1383
- Added option to disable Apple bot check in `UnsubscribeInactiveUsersCommand`. remp/remp#1396

---

[3.11.0]: https://github.com/remp2020/remp/compare/3.10.0...3.11.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
