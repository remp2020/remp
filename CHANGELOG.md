# Changelog

All notable changes to this project will be documented in this file.

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Added support for conversion rate sorting in Conversions data table. remp/remp#306
- Fixed error thrown when using main search on authors listing. remp/remp#531
- Added command to maintain data retention for rolled-over Elastic indices. remp/remp#527
- Pageviews graph in article details page loads data from Journal snapshots instead of directly quering Journal API by default. Option `PAGEVIEWS_DATA_SOURCE` added to `.env` file to allow switching to old behaviour. remp/remp#442
- `rempConfig.tracker` configuration option `explicit_referer_medium` is deprecated and renamed to `refererMedium`. The old one is still accepted due to compatibility reasons.
- Added support for referer medium renaming - one can specify label for each medium by adding a record to `referer_medium_labels` table. remp/remp#543
- Updated layout footer with link to REMP website. remp/remp#522 HEAD
- Added ability to display external events in article detail and dashboard. remp/remp#574
- Added (optional) `article_id` field to event parameters pushed to tracker. remp/remp#556
- Fixed property naming validator issue checking unique constraint against account names, not property names.
- Added article conversion/pageviews stats filtering based on selected property. GH-50
- Added support for article tags (storing tags associated with articles, filtering in data tables). remp/remp#217

### [Campaign]

- Updated layout footer with link to REMP website. remp/remp#522
- Fixed missing validation rules for collapsible bar banner template. remp/remp#558
- Added support for campaign segment exclusion. User now need to match both inclusion and exclusion segment rules to see the campaign banner. GH-33
- Overal rectangle banner button text and main text attributes are now optional. This is useful when e.g. only using picture inside the banner. remp/remp#582

### [Mailer]

- **BREAKING**: Attachment parameter in `send-email` API is now required to be base64-encoded to support PDF (and other binary) attachments.
- **BREAKING**: Context checking during email sending now only checks if user received an email with given context before; Mailer ignores `mail_template_code` being sent. In previous version, two different mail templates with same context could be sent to the same user. This version prevents such behavior. remp/crm#987
- Updated layout footer with link to REMP website. remp/remp#522
- Added possibility to filter hermes payload parameters in logs. Parameters `password` and `token` are already filtered by default. See `config.neon` for reference how to extend filtering with own parameters.
- Added hermes handler to unsubscribe users from mail type if emails are dropped. You can enable the feature in `config.local.neon` (see example file for reference). remp/remp#566
- Added configuration for allowed preflight (`OPTIONS`) headers into configuration. You can configure them via `%preflight.headers` parameter - see [example use](Mailer/app/config/config.neon).
- Fixed possible duplicate email for same context being sent, in case the emails were scheduled at the same time via `send-email` API.
- Fixed and redesigned mailing list variants statistics to be sortable and filterable. remp/remp#593
- Fixed attachment_size column type - changing to integer (from incorrectly assigned datetime).

### [Sso]

- Updated layout footer with link to REMP website. remp/remp#522


## [0.9.1]

### [Beam]

- Numeric columns in conversion are sorted descending first (and only way). remp/remp#306
- Fixed occasional duplicate records in article listings across Beam. remp/remp#482
- Fixed missing articles on author detail if the article doesn't belong to any section. 

### [Campaign]

- Added support for one-time banners. remp/remp#512
- Unified z-index of banner in all templates to 100000. remp/web#968

### [Mailer]

- Fixed possible error on newsletter list editing if custom sorting was used. remp/remp#516
- Fixed autologin parameter in unsubscribe email links. remp/remp#518

---

## [0.9.0] - 2019-10-04

> **Elasticsearch upgrade notice**. We'll be raising Elasticsearch compatibility to 7.* in the beginning of 2020 to keep with the latest changes. Current implementation Segments API is tested and maintained again Elasticsearch 6.*. Please plan your upgrade accordingly.

> **Go** was updated to version **1.13**. 

### [Beam]

- Major refactoring and redesign of IOTA (on-site stats) which now include more relevant statistics. GH-24
- Article detail now shows referer (traffic source) statistics. remp/remp#445
- Added command for compressing snapshot data. remp/remp#442
- Timespent interval configuration in JS is now configurable. remp/remp#461
- Added configuration for various views of conversion rate (decimals, multiplier). remp/remp#475
- Added support for property token selection on Beam dashboard. remp/remp#473

### [Campaign]

- Bugfixed notice in showtime when adblock was not detectable. remp/remp#447
- Campaign listing now displays segment names instead of segment codes.
- Bugfixed campaign form not being able to update some options to their default value.
- Added HTML overlay banner template. remp/remp#457
- Added support to pass custom query parameters to displayed banner URL. 
- All links in banner (not just CTA) now include Campaign's UTM parameters. remp/remp#455

### [Mailer]

- Updated Hermes library, logging only error events to the database.
- Refactored UTM parameter replacing in email links.
- Added support to send single emails via API.

## [0.8.2] - 2019-07-11

_Note: generated binaries are the same as in 0.8.1, no need to deploy them if you have 0.8.1 deployed._

> **Elasticsearch upgrade notice**. We'll be raising Elasticsearch compatibility to 7.* in the beginning of 2020 to keep with the latest changes. Current implementation Segments API is tested and maintained again Elasticsearch 6.*. Please plan your upgrade accordingly.

### [Beam]

- (GO) Removed unused vendor files.

### [Campaign]

- Tables `campaign_banners` and `campaign_country` didn't have primary keys since creation. This was preventing migrations on the replicated instances. This release fixes the failing migration issue. https://github.com/remp2020/remp/commit/f211d2c5d40fa3cb5a232396210543e397c6c439

### [Mailer]

- Exit ProcessJobStatsCommand if there is nothing to do.
- Removed obsolete command `ProcessTemplateStatsCommand`. Use `AggregateMailTemplateStatsCommand`.
- Fixing error screen in case SSO wasn't able to log user in https://github.com/remp2020/remp/commit/7fd415459f012c29c991ae763d74c1f0a49fe2cd
  
  Please change following line in your `.env` file in Mailer:
  
  ```
  SSO_ERROR_URL=http://mailer.remp.press/sign/error
  ```


## [0.8.1] - 2019-07-10

> **Elasticsearch upgrade notice**. We'll be raising Elasticsearch compatibility to 7.* in the beginning of 2020 to keep with the latest changes. Current implementation Segments API is tested and maintained again Elasticsearch 6.*. Please plan your upgrade accordingly.


### [Beam]

- Added experimental database-based dashboard data source which works with snapshot of "concurrents" data made every minute. Snapshotting is being done automatically for you against *concurrents_by_browser* elastic index. This should be populated by your Telegraf instance based on [similar configuration](https://github.com/remp2020/remp/blob/master/Docker/telegraf/telegraf.conf#L73).

- [Segments]: Fixing mapping fetching changes which was causing "include_type_name" deprecation notices since Elastic 6.8. This is to prepare to provide full compatibility for Elastic 7.* and drop compatibility promise for 6.* in the following months.


## [0.8.0] - 2019-07-08

_Note: Generated binaries were not changed since 0.7.0, there's no need to redeploy them if you have 0.7.0 deployed._

### [Beam]

- New functions for tracking commerce events were added to give caller more precise control over what is being tracked.  See documentation of following methods in https://github.com/remp2020/remp/tree/master/Beam#js-tracking-interface:

  - `remplib.tracker.trackCheckoutWithSource`
  - `remplib.tracker.trackPaymentWithSource`
  - `remplib.tracker.trackPurchaseWithSource`
  - `remplib.tracker.trackRefundWithSource`

- In some cases, Beam's database could have been migrated with invalid default value for conversion's _paid_at_ column. https://github.com/remp2020/remp/commit/553c2dcf9120b0b54e938d7e3f5f1a9dca3a73e2

### [Campaign]

- New configuration options were added to append extra query parameters to banner's target URL in case target page needs to identify source page/article and referer is not enough.

  See `rempConfig.campaign.bannerUrlParams` object at https://github.com/remp2020/remp/tree/master/Campaign#javascript-snippet for more detailed info:

  ```
  bannerUrlParams:  {
      "foo": function() { return "bar" },
      "baz": function() { return "XXX" }
  }
  ```

- HTML banner now supports custom JS and CSS inclusion from external sources.
- Bugfixing HTML banner's Farbtastic color picker which was not being initialized after the latest dependency changes.
- Material checkbox was not being rendered properly in Mailer causing custom generator implementations not being able to use it.

### [Mailer]

- If generated queue was too big, memory exhausted exception was possible. Pagination was added to queue generation prevent the issue.


---

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

[0.8.0]: https://github.com/remp2020/remp/compare/0.7.0...0.8.0
[0.8.1]: https://github.com/remp2020/remp/compare/0.8.0...0.8.1
[0.8.2]: https://github.com/remp2020/remp/compare/0.8.1...0.8.2
[0.9.0]: https://github.com/remp2020/remp/compare/0.8.2...0.9.0
[0.9.1]: https://github.com/remp2020/remp/compare/0.9.0...0.9.1
[Unreleased]: https://github.com/remp2020/remp/compare/0.9.1...master
