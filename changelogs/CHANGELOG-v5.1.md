## [5.1.0] - 2026-06-23

### Project

- Removed unused `chart.js` and `vuefilters.js` from the shared `remp/js-commons` library.
- Fixed `DateTimePickerWrapper` to support Vue v3.

### [Beam]

- Removed reference to `vuefilters` from the shared `remp/js-commons`, they weren't activelly used anywhere.
- [Segments] Migrated Elasticsearch client from `olivere/elastic/v7` to `go-elasticsearch/v8` TypedAPI . `olivere/elastic/v7` dependency removed. remp/remp#1436
    - **Removed:** `olivere/elastic/v7` library and all implementations based on it (`ElasticDB`, `EventElastic`, `PageviewElastic`, `CommerceElastic`, `ConcurrentElastic`).
    - **Removed:** Scroll API usage — replaced by `search_after` + Point-In-Time (PIT) for all paginated listing operations. PIT opens a frozen index snapshot for the duration of pagination, eliminating duplicate/missing documents caused by concurrent writes. Scroll API keeps all search context in JVM heap; PIT does not.
- Fixed MySQL 8.4 compatibility issues in DB schema. remp/remp#1474
- Fixed input label visual issue if the input was populated programatically. remp/remp#1476

### [Campaign]

- Fixed chart.js configuration objects to match the current library version.
- Added IP address targeting for campaigns with whitelist/blacklist support for single IPs and ranges. remp/euobserver#132
- Moved `vuefilters` internally from the shared library inside the project.
- Fixed JS errors and possibly broken click event on the banner edit page. [remp2020/remp#236](https://github.com/remp2020/remp/pull/236)
- Fixed Bar template mobile layout when close button is used. remp/helpdesk#4684
- Fixed input label visual issue if the input was populated programatically. remp/remp#1476
- Fixed banner JS includes loading order, execute in the declared order instead of racing. remp/helpdesk#4673

### [Mailer]

- **IMPORTANT**: Changed column `user_id` to nullable in `mail_user_subscriptions` table (migration included).
    - This migration is non-blocking but it may take some time - e.g locally, 30M entries takes about 5 minutes.
- **BREAKING**: Added new required env variable `APP_URL` — base URL of the Mailer admin. remp/remp#1516
  - Make sure this variable is set, it's required for native unsubscribe links.
- **BREAKING**: Added support for "external" mail types — newsletters that can be sent to subscribers without user account in CRM. remp/remp#1516
    - Added `is_external` boolean column to `mail_types` table (migration included).
    - External mail types skip segment selection when creating/editing mail jobs — recipients are resolved directly from subscriptions.
- **BREAKING**: Made `user_id` optional in subscribe/unsubscribe/is-subscribed/is-unsubscribed/bulk-subscribe/user-preferences API endpoints. remp/remp#1516
    - For non-external mail types, `user_id` is still required and validated at the handler level.
    - For external mail types, `user_id` can be omitted.
- **BREAKING**: Changed `UserSubscriptionsRepository::allSubscribers()` to `allSubscribersWithUserId()`, filtering out subscribers without `user_id` from segment results. remp/remp#1516
- **BREAKING**: Class `GenericPageContent` was renamed to `OpenGraphPageContent` to specifically identify source of the metadata. remp/remp#1477
- **BREAKING**: Interface `ShopContentInterface` and its implementations are moved out of the Mailer module to the internal part of the app. remp/remp#1477
    - In case you used it, feel free to copy the implementation to your skeleton app and maintain it further yourself.
- Added `is_external` field to mail type listing API responses (v1, v2, v3). remp/remp#1516
- Added `is_external` field to mail type upsert API response and creation. remp/remp#1516
- Added `is_external` checkbox to the mail type (newsletter list) create/edit form. remp/remp#1516
- Fixed `mail_types.code` index to be unique for MySQL 8.4+ compatibility.
  - The foreign key from `mail_user_preferences.code` requires the referenced column to have a unique index.
- Added a priority parameter to the content generator replacers register method to ensure the replacers are applied in the correct order. remp/remp#1450
- Added AnchorWirelinkReplace content generator replacer to support deeplinking in emails. remp/remp#1450
- Added admin UI for managing mail type variants (`ListVariantPresenter`) — list, create, edit, show, and soft-delete variants from the newsletter list detail page. remp/remp#1516
- Added ability to mark a mail type as multi-variant from the newsletter list form (`is_multi_variant` checkbox). remp/remp#1516
- Added `SubscribersImporter` model for bulk-subscribing emails to a mail type, with optional pruning of subscribers missing from the imported list. remp/remp#1516
- Added "Import subscribers" admin flow for external mail types (`ListSubscribersImportFormFactory`) — supports selecting target variants, forcing no-variant subscription, and removing missing subscribers. remp/remp#1516
- Added "Import subscribers" admin flow per variant (`VariantSubscribersImportFormFactory`) for external mail types. remp/remp#1516
- Added `MailSettingsPresenter` with public `unSubscribeEmail`, `unSubscribeSuccess`, and `tokenExpired` routes — native Mailer-side unsubscribe pages that don't require a CRM round-trip. remp/remp#1516
- Added `mailer_unsubscribe` template variable available in all emails when an autologin token is present, linking to the new native (directly in Mailer) unsubscribe page. remp/remp#1516
- Added `actionButtons` table setting to the shared `DataTable` component — renders custom action buttons (URL + label) in the data table toolbar. remp/remp#1516
- Fixed autologin token handling in the unsubscribe flow. remp/remp#1516
- Fixed `ListVariantsRepository` to order variants by count correctly. remp/remp#1516
- Fixed `RulesTrait` not formatting `[caption]` tags in generator with its designated template but rather with `$imageTemplate`. remp/euobserver#181
- Fixed temporary duplication of Mailgun webhook events in the sending summary widget. remp/euobserver#162
    - Subsequent events coming from Mailgun weren't previously filtered and always incremented stat counters. Multiple opens/clicks in the email caused repeated incrementation of a metric.
    - The stats were always recalculated and corrected by `mail:job-stats` command, which should be run at least daily.
- Added `--from` option to `mail:job-stats` command.
    - The parameter only evaluates batches which report activity in their mail logs within the selected time range. If not used, all batches are aggregated.
- Fixed `AnchorWirelinkReplace` to expose `rtm_click` on the outer Wirelink URL so the Mailgun click webhook can increment `mail_template_links.click_count`. remp/helpdesk#4617
  - Fixed `RtmClickReplace::setRtmClickHashInUrl`, `removeRtmClickHash`, and `getRtmClickHashFromUrl` to handle URL fragments; previously any anchor href containing `#` had `rtm_click` appended inside the fragment, causing the Mailgun click webhook to silently ignore those events.
- Added server-side Twig rendering for email preview. Preview now correctly renders snippets and layouts instead of stripping Twig syntax client-side. remp/remp#1434
    - Added `Template:renderContentPreview` AJAX endpoint that renders HTML/text content via `TwigEngine` with correct snippets scoped to the mail type.
    - `MailPreview` Vue component now fetches rendered preview from `previewUrl`.
- Fixed input label visual issue if the input was populated programatically. remp/remp#1476
- Updated Article URL parser widget to support universal layout. remp/remp#1471
- Updated `NytContent` implementation to use Article Search v2 API instead of now unavailable news v3 API.
- Added `JsonLDContent` content metadata extractor using JSON+LD schema found at the target URL. remp/remp#1477

### [Sso]

- Fixed input label visual issue if the input was populated programatically. remp/remp#1476

[5.1.0]: https://github.com/remp2020/remp/compare/5.0.0...5.1.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
