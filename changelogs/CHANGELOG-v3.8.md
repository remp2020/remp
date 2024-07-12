## [3.8] - 2024-07-12

### [Beam]

- Added a custom Carbon date request validator and integrated it into `ArticleDetailsController::dtReferers` to validate `visited_to` and `visited_from` request inputs. remp/remp#1101
- [Tracker] Updated list of referer sources so that the newer social/searches are recognized. remp/remp#1313
- Fixed an XSS vulnerability within creating and editing a segment. remp/remp#1343
- Added mobile concurrents percentage value to `DashboardController::mostReadArticles` response. remp/remp#1352
- Added custom Carbon date request validator to validate date inputs in requests. remp/remp#1101

### [Campaign]

- Fixed an XSS vulnerability within creating and editing a campaign. remp/remp#1343
- **IMPORTANT**: Added unique suffix to banner position (for hidden html banners) in `Showtime::prioritizeCampaignBannerOnPosition`. Prevents suppressing other banners. remp/remp#1346
- Added custom Carbon date request validator to validate date inputs in `campaigns/{campaign}/stats/data` API endpoint. remp/remp#1101

### [Mailer]

- Changed the maximum length of the `name` column in the `mail_templates` table to 768 characters. remp/remp#1257
- Added match `x.com` domain in `EmbedParser::isTwitterLink`. remp/helpdesk#2759
- Updated Chart.js library which handles charts in the application. remp/remp#1361
- Fixed chart on the newsletter list detail, so it makes some sense now. remp/remp#1361
- Added ability to edit newsletter list category. remp/remp#724
- Added option to prefill newsletter list preview URL by using existing email's public preview URL. remp/remp#724
- Added support for group actions in `data_table.latte`. remp/remp#724
- Added edit button to newsletter list detail. remp/remp#1367
- Changed `Preview url` and `List image` buttons to disabled if their url is not provided. remp/remp#1367
- Added edit and show button to sent emails detail. remp/remp#1367
- Added preview and fullscreen edit for text layout. remp/remp#1094
- Added `locked` parameter to `NewsfilterGenerator` params to differentiate between locked and unlocked version when rendering template. remp/remp#1358

---

[3.8]: https://github.com/remp2020/remp/compare/3.7.0...3.8.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
