## [4.1.0] - 2025-06-27

### [Beam]

- Fixed sorting issues in datatables. remp/remp#1409
- Added impression tracking feature. remp/remp#1228

### [Campaign]

- Fixed cast string to boolean in form inputs before validation in `CampaignRequest::prepareForValidation`. remp/crm#3472
- Added `data-href` attribute with target URL of banner to banners which lost this attribute during accessibility-related changes.
- Changed HTML banner rendering for JS-based banners; the HTML is not being rendered anymore to avoid accessibility warnings.
- Added information about which campaigns (including variants and banners) were displayed to the Campaign Debugger. remp/helpdesk#3591
- Added referal property check into Showtime. remp/remp#1290
- Updated chart library to the latest version. remp/remp#1287
- Fixed snippet editor removing HTML content of JS strings. remp/remp#1405

### [Mailer]

- Added generator template validation. remp/remp#1398
- Added support for the processing of additional elements in `RespektContent`. remp/respekt#388
- Added HTML WYSIWYG editor for URL Parser email generator intro and footer inputs. remp/remp#1416
- Added extra index to improve performance of dashboard loading. remp/remp#1418
- Added setup method for `EmbedParser` to preprocessing of thumbnail image. remp/remp#1411
- Fixed memory limit issue when editing newsletter list with many emails. remp/remp#1420

## [4.1.1] - 2025-07-07

### [Campaign]

- Fixed condition in `NewsletterRectangleTemplate#getConfig()` that checks if `banner_config` is set. Property is initialized as empty array so `isset()` is not enough. remp/remp#1423

[4.1.0]: https://github.com/remp2020/remp/compare/4.0.0...4.1.0
[4.1.1]: https://github.com/remp2020/remp/compare/4.1.0...4.1.1

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
