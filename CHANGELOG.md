# Changelog

All notable changes to this project will be documented in this file.

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### Docker

- Added tzdata installation for remp_segments docker (required by golang).

### [Beam]

- Fixed ignored explicit `browserId` parameter in JS configuration. remp/remp#690

## [0.11.1] - 2020-07-10

### [Beam]

- Added environment variables to configure Redis databases in Laravel. remp/remp#671 

### [Campaign]

- Added environment variables to configure Redis databases in Laravel. remp/remp#671

### [Sso]

- Added environment variables to configure Redis databases in Laravel. remp/remp#671

## [0.11.0] - 2020-06-30

### Important

**Elasticsearch upgrade notice.** We'll be raising Elasticsearch compatibility to 7.* in the next release. Up until now, Segments API supported Elasticsearch 6.*.

We recommend two upgrade scenarios:

- When new release is ready, you can upgrade your existing cluster based on the documentation available at https://www.elastic.co/guide/en/elasticsearch/reference/current/setup-upgrade.html.
- If you clear your Elastic data recurrently and archive stored events to CSV, you can spin up new v7 cluster and configure [Telegraf](./Docker/telegraf/telegraf.conf) to push data to both v6 and v7 of Elastic. Once you're satisfied with the amount of data in v7 (~1 month tends to be sufficient), wait for the next release and change Elastic address in Segments API to v7 cluster. If there are no issues, you can stop pushing new events to v6 cluster in Telegraf and stop the cluster completely. 

### Docker

- **BREAKING**: Replaced `spotify/kafka` docker image with `wurstmeister/kafka` as original image was no longer maintained and new installations stopped working. remp/remp#638
  
  - In case you have existing installation in place using the docker compose, please run:
    ```bash
    docker-compose stop beam_tracker telegraf
    docker-compose rm beam_tracker telegraf
    docker-compose build beam_tracker telegraf
    ```  

### [Mailer]

- Added API endpoint `mailers/mail-type-categories` to list mail type categories. GH-64
- Extended API endpoint `mailers/mail-types` to include additional data and filter via `public_listing` and `code` parameters. GH-64
- Added API endpoint `users/is-unsubscribed` to check if user is explicitly unsubscribed from the newsletter. GH-64
- Added API endpoint `users/logs-count-per-status` to get number of emails sent to user per each status within provided timeframe. GH-64
- Added API endpoint `users/logs` to retrieve logs of emails sent to user. Various filter can apply, see [README.md](./Mailer/README.md) for more details. GH-64
- Added JSON schema validation to `Subscribe` and `BulkSubscribe` APIs. GH-64
- Added API endpoint `users/preferences` to read user's subscriptions to newsletters (mail types). GH-64
- Improved speed of job detail page - unsubscribe stats could slow rendering a bit if job was sent to 6+-figure recipients. remp/remp#624
- Added API endpoint `mailers/mail-templates` to list and filter available mail templates.
- Added early-version support for search in the top searchbox. Searchable are emails (templates), layouts, jobs and newsletter lists. GH-69
- Added early-version support for WYSIWYG editor in Template edit form. It's configurable in `config.local.neon`, Mailer keeps HTML editor as default for now. GH-58

### [Beam]

- Added early-version support for search in the top searchbox. Searchable are articles, authors, sections, tags and segments. GH-62
- Added support for timezone parameter in Journal aggregations. remp/remp#605
- Quick range day filters now start from beginning of the day. remp/remp#605
- Added `FORCE_HTTPS` environment variable to enforce HTTPS generation to URLs instead of determining protocol based on the request. This is useful in case you're running your application on `https`, but internally use proxy forwarding the request via `http`. remp/remp#619
- Added new APIs `api/authors/top` and `api/tags/top` for retrieving top authors and tags per given time period. remp/web#366
- Articles upsert v2 api endpoint - process article titles only if they are present in payload. remp/remp#646
- Fixed remplib initialization which could use misidentification of user - `remplib.getUserId()` would return `null` even when the `userId` was correctly set in `rempConfig`. remp/remp#651

### [Campaign]

- Added early-version support for search in the top searchbox. Searchable are banners and campaigns. GH-62
- Improved intervals in campaign stats charts. remp/remp#605
- Quick range day filters now start from beginning of the day. remp/remp#605
- Added `FORCE_HTTPS` environment variable to enforce HTTPS generation to URLs instead of determining protocol based on the request. This is useful in case you're running your application on `https`, but internally use proxy forwarding the request via `http`. remp/remp#619
- Fixed add new ab variant replaces last variant instead of adding new after last one. remp/remp#634
- Added option to disable banner events tracking. remp/remp#636
- Added ability to access banner properties in custom JS code run in banner via newly added `params` object. remp/remp#636
- Changed wording of hints in campaign's segment selection form. remp/remp#645
- Fixed remplib initialization which could use misidentification of user - `remplib.getUserId()` would return `null` even when the `userId` was correctly set in `rempConfig`. remp/remp#651

### [Sso]
- Added `FORCE_HTTPS` environment variable to enforce HTTPS generation to URLs instead of determining protocol based on the request. This is useful in case you're running your application on `https`, but internally use proxy forwarding the request via `http`. remp/remp#619

---

## [0.10.0] - 2020-04-07

- Datatables in all projects now store filters/ordering in URL hash, not in local storage. Previous solution was buggy and didn't allow users to share their filters with other users. GH-56
- Javascript building now supports linking of shared `remp` package within the projects. You can now link the package with `yarn link --cwd ../Package/remp && yarn link remp` inside the app folder. Command `yarn watch` is able to watch for changes in the shared package. GH-63

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
- Added top articles endpoint for listing top articles by time, sections. remp/web#1010
- Fixed duplicate conversions if multiple sections/tags/authors were linked to article.
- Fixed missing conversions if no section/tag/author was linked to article. remp/remp#586
- Added articles upsert v2 api endpoint because of added `external_id` to `tags`, `authors` and `sections` tables. remp/remp#599

### [Campaign]

- Updated layout footer with link to REMP website. remp/remp#522
- Fixed missing validation rules for collapsible bar banner template. remp/remp#558
- Added support for campaign segment exclusion. User now need to match both inclusion and exclusion segment rules to see the campaign banner. GH-33
- Overal rectangle banner button text and main text attributes are now optional. This is useful when e.g. only using picture inside the banner. remp/remp#582
- Texts in collapsible bar, bar and medium rectangle bar templates are now optional. remp/remp#597

### [Mailer]

- **BREAKING**: Attachment parameter in `send-email` API is now required to be base64-encoded to support PDF (and other binary) attachments.
- **BREAKING**: Context checking during email sending now only checks if user received an email with given context before; Mailer ignores `mail_template_code` being sent. In previous version, two different mail templates with same context could be sent to the same user. This version prevents such behavior. remp/crm#987
- **BREAKING**: Removed hardcoded support for Errbit/Airbrake error logging, added support for Sentry logging. See README for details on how to configure Sentry to track errors.
- Updated layout footer with link to REMP website. remp/remp#522
- Added possibility to filter hermes payload parameters in logs. Parameters `password` and `token` are already filtered by default. See `config.neon` for reference how to extend filtering with own parameters.
- Added hermes handler to unsubscribe users from mail type if emails are dropped. You can enable the feature in `config.local.neon` (see example file for reference). remp/remp#566
- Added configuration for allowed preflight (`OPTIONS`) headers into configuration. You can configure them via `%preflight.headers` parameter - see [example use](Mailer/app/config/config.neon).
- Fixed possible duplicate email for same context being sent, in case the emails were scheduled at the same time via `send-email` API.
- Fixed and redesigned mailing list variants statistics to be sortable and filterable. remp/remp#593
- Fixed attachment_size column type - changing to integer (from incorrectly assigned datetime).
- Added option to select events to handle when starting Hermes worker in case there's need to run separate workers for mission-critical event types.
- Fixed possibly incorrectly skipped newsletter subscription in user registered API handler. remp/crm#1159

### [Sso]

- Updated layout footer with link to REMP website. remp/remp#522

---

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

[0.10.0]: https://github.com/remp2020/remp/compare/0.9.1...0.10.0
[0.9.0]: https://github.com/remp2020/remp/compare/0.8.0...0.9.1
[0.8.0]: https://github.com/remp2020/remp/compare/0.7.0...0.8.0
[0.8.1]: https://github.com/remp2020/remp/compare/0.8.0...0.8.1
[0.8.2]: https://github.com/remp2020/remp/compare/0.8.1...0.8.2
[0.9.0]: https://github.com/remp2020/remp/compare/0.8.2...0.9.0
[0.9.1]: https://github.com/remp2020/remp/compare/0.9.0...0.9.1
[Unreleased]: https://github.com/remp2020/remp/compare/0.10.0...master
