# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- **BREAKING**: [Segments] Removed the `cache` parameter and the `cache`, `event_rules`, `overridable_fields` and `flags` response fields from the `/segments/{code}/users/check/{user_id}` and `/segments/{code}/browsers/check/{browser_id}` endpoints. Feature has not been actually used for some time. remp/remp#772
  - Older Campaign versions expect the removed fields and may fail to evaluate Beam segments. Deploy this version of Campaign before or together with Beam Segments API.
  - If you call these endpoints from your own code, stop reading the removed fields.
- Removed the client-side segment rule cache from `remplib.js`. The tracker no longer stores segment rules or event counts in `localStorage` (and removes the `segment_provider_cache` and `beam_event_rules_map` keys left there by previous versions). remp/remp#772
- Lowered z-index of the iota on-site overlay to 9996–9998 so it no longer covers Campaign banners (9999 and above). remp/helpdesk#5008
- Health check endpoint (`/health`) no longer exposes error details (file paths, stack traces) in `context` of failed checks unless debug mode is enabled; details are written to the application log instead. remp/crm#1796
- [Segments] Fixed unnecessary warnings logging when aggregation did not match any records. remp/remp#1514  
- Fixed newsletter edit form ignoring the stored recurrence and always showing "Repeat every 1 day". remp/remp#1515 
- Fixed possibility of `newsletters:send` crashing on newsletters with personalized content.
- Fixed `/api/articles` running a query for each tag and returning `null` names of authors, sections and tags. Article store and v1 upsert responses now eager load tag categories too.

### [Campaign]

- **BREAKING**: Removed `setProviderData()` and `getProviderData()` from `SegmentContract` and `SegmentAggregator`. They only passed the unused client-side Beam segment cache between the browser and the Segments API. remp/remp#772
  - Custom segment providers can delete their implementations of these methods; keeping them does no harm.
  - Remove any of your code calling these methods on `SegmentAggregator`.
- **BREAKING**: Removed the `$providerData` parameter from `ShowtimeResponse::success()`. Custom `ShowtimeResponse` implementations have to drop the parameter from their `success()` method. remp/remp#772
- **BREAKING**: Removed `providerData` from the showtime response and the deprecated `campaign_showtime` browser event, which only carried `providerData`. If you listen to `campaign_showtime`, switch to `campaign_showtime_response`. remp/remp#772
- **BREAKING**: Segment providers are registered to `SegmentAggregator` via a factory implementing the new `SegmentContractFactory` instead of the `SegmentContract` implementation itself. Showtime now creates the segment providers from the factories' plain config instead of unserializing the whole `SegmentAggregator` (stored as a serialized closure) on every request. remp/remp#1527
  - If you have a custom segment provider, create a factory for it: `config()` reads the configuration within Laravel, `create()` creates the provider from it without Laravel. Tag the factory with `SegmentAggregator::TAG` instead of the provider. See the [Segment integration](Campaign/extensions/campaign-module/README.md#segment-integration) docs for an example.
  - Removed `RedisAwareInterface` and `SegmentAggregator::refreshRedisClient()`. If your provider implemented the interface, remove it and use the Redis client passed to the factory's `create()`.
  - `SegmentAggregator` constructor now takes the config of the factories. If you create it yourself, use `SegmentAggregator::fromFactories()`.
  - Renamed `SegmentAggregator::serializeToRedis()` to `cacheConfig()` and `unserializeFromRedis()` to `fromCachedConfig()`. The banner config maps (dimensions, positions, alignments, color schemes) are cached by `campaigns:refresh-cache` directly.
  - Removed the `SerializeSegmentAggregator` middleware. It wasn't registered since the upgrade to Laravel 12.
- **BREAKING**: Removed `Showtime::setPositionMap()`, `setDimensionMap()`, `setAlignmentsMap()` and `setColorSchemesMap()`. Showtime always loaded the maps cached by `campaigns:refresh-cache` from Redis, the setters were used only in tests. If you call them, remove the calls. remp/remp#1527
- **IMPORTANT**: Showtime doesn't display any campaign after the deploy until `campaigns:refresh-cache` caches the segment aggregator config under the new `segment_aggregator_config` Redis key and re-caches the campaigns, which don't contain their banners anymore.
  - Run `php artisan campaigns:refresh-cache` right after the deploy.
  - The old `segment_aggregator` key is not used anymore; you can delete it from Redis.
- **IMPORTANT**: The `data-href` attribute of Overlay Rectangle, HTML and HTML Overlay banners now contains the tracking parameters (`rtm_*`), same as the `href` of the banner link it replaced. remp/helpdesk#5042 
  - If your custom code reads `data-href` and appends the tracking parameters itself, remove that to avoid duplicate parameters.
- **IMPORTANT**: Banner JS and CSS includes are loaded only once per page, even if multiple displayed banners use the same include. remp/remp#1521
  - If your include initializes something for a specific banner when it's executed, move that code to the custom JS of the banner.
- **IMPORTANT**: One-time banners created before the deploy (via `/api/banners/{banner}/one-time-display`) are not displayed after it. remp/remp#1522
  - If some of them still have to be displayed, create them again via the API after the deploy.
- `remplib.js` no longer sends the client-side segment cache in the showtime request, which made the request URL grow with every evaluated segment rule. remp/remp#772
- Showtime evaluates segment rules after pageview attribute rules, so it doesn't check segments (possibly via HTTP request to the segment provider) of campaigns not matching the pageview attributes. remp/remp#1527
- Showtime reads its data from Redis in fewer round trips and creates the Sentry client only when something is logged. remp/remp#1527
- Showtime doesn't detect the device of campaigns targeting both desktop and mobile devices, as the detected device can't reject such campaigns. remp/remp#1527
- Cached campaigns don't contain their banners anymore, showtime loads banners from their own cache only for the variants to display. remp/remp#1528
- Added `From` option to campaign `Every N page views` display rule, allowing the banner to start displaying at a later pageview than the first one. remp/remp#1488
- Fixed overlay banners taller than the viewport (e.g. phone in landscape) being cut off and unclosable. The backdrop now scrolls. remp/helpdesk#5008
- Fixed banners with a publisher-defined color scheme (`config/banners.local.php`) failing to render after a deploy, because `campaigns:refresh-cache` ran without the local config and overwrote the cached schemes with the defaults. An unknown scheme now fails with an error naming the banner, the scheme and the available schemes, and saving a banner or campaign in admin re-serializes the config maps to Redis. remp/helpdesk#5008
- Changed the JSHint linter in the snippet and banner custom JS editors to check the code as ES6 (`esversion: 6`) instead of ES5, so `const`, arrow functions and template literals are no longer reported as errors. Newer syntax (optional chaining, nullish coalescing) is still flagged, because it may not be supported by older browsers. remp/remp#1486
- Fixed missing tracking parameters (`rtm_*`) in the target URL when clicking the button or banner area outside the main link of Overlay Rectangle, HTML and HTML Overlay banners. remp/helpdesk#5042
- Health check endpoint (`/health`) no longer exposes error details (file paths, stack traces) in `context` of failed checks unless debug mode is enabled; details are written to the application log instead. remp/crm#1796
- Added bulk editing of IP address targeting in the campaign form. The new `Edit as list` button opens the whole list in a text area, one IP address or range per line. Adding a single address trims the input and skips duplicates; addresses are validated on save. remp/euobserver#281
- Added IPv6 support to campaign IP address targeting (single addresses and ranges; a range must be either IPv4 or IPv6). remp/euobserver#281
- Changed campaign cache to store IP ranges and countries once instead of duplicating them into whitelist/blacklist copies. remp/euobserver#281
- Fixed `banner.js` being loaded and executed once for every banner displayed on the page, which inserted a full copy of all banner template styles into the page each time. `remplib.loadScript()` and `remplib.loadStyle()` now load each URL only once per document. remp/remp#1521
- Fixed banner transition styles leaking into the page displaying the banner. remp/remp#1521
- Changed showtime to look up one-time banners without the Redis `KEYS` command. remp/remp#1522
- Fixed showtime accepting an empty debug key when `CAMPAIGN_DEBUG_KEY` was configured but empty, which let anyone enable the campaign debugger.
- Fixed one-time banners failing to display with `Banner with ID ... is not present in cache` error since the banner cache was changed to JSON in 4.0. remp/remp#1522

### [Mailer]

- **BREAKING**: Removed `JsonLDContent::postProcessMeta()` in favor of the narrower `processImage(?string): ?string` and `processAuthors(array): array` hooks, which `JsonLDContent` applies before constructing `Meta`. remp/remp#1499
- Added audio metadata parsing to `JsonLDContent`. remp/remp#1499
- `JsonLDContent` now also accepts a plain URL string in the schema's `image` property. Previously only an `ImageObject` (or an array of them) was read.
- Refactored X embedding from `Euobserver\EmbedParser` to the shared `Remp\Mailer\Models\Generators\EmbedParser::fetchXPreviewImage()`. remp/remp#1505
- Added support for a locked (non-subscriber) variant of EUobserver newsletters, first used by the This week newsletter. remp/euobserver#262
  - An article generator registered with `lockingEnabled: true` cuts the content at the article's `eo/lock` block and appends the `eo-subscribe-cta` snippet, which must exist in Mailer.
  - The generator response then also contains `lockedHtmlContent`/`lockedTextContent`, based on which the Hub creates separate jobs for subscribers and non-subscribers.
- Added "Sign out and log in with a different account" button to the sign-in error page, so users signed into CRM with a wrong account are no longer stuck. remp/remp#1507
  - Shown when the configured `authenticator` implements the new `SignOutUrlProviderInterface`.
- Health check endpoint (`/health`) no longer exposes error details (file paths, stack traces) in `context` of failed checks unless debug mode is enabled; details are written to the application log instead. remp/crm#1796
- Fixed external newsletter lists getting a subscription record for every user from the user base. remp/web#3108
- Added signing of image URLs, so Mailer can add its own parameters (resizing, `rtm_*` tracking) to images served through a signing image proxy. remp/remp#1516
- Added mail type and template's public preview URL to the `/api/v1/users/logs` API response. remp/crm#3904

### [Sso]

- Health check endpoint (`/health`) no longer exposes error details (file paths, stack traces) in `context` of failed checks unless debug mode is enabled; details are written to the application log instead. remp/crm#1796

## Archive

- [v5.2](./changelogs/CHANGELOG-v5.2.md)
- [v5.1](./changelogs/CHANGELOG-v5.1.md)
- [v5.0](./changelogs/CHANGELOG-v5.0.md)
- [v4.3](./changelogs/CHANGELOG-v4.3.md)
- [v4.2](./changelogs/CHANGELOG-v4.2.md)
- [v4.1](./changelogs/CHANGELOG-v4.1.md)
- [v4.0](./changelogs/CHANGELOG-v4.0.md)
- [v3.11](./changelogs/CHANGELOG-v3.11.md)
- [v3.10](./changelogs/CHANGELOG-v3.10.md)
- [v3.9](./changelogs/CHANGELOG-v3.9.md)
- [v3.8](./changelogs/CHANGELOG-v3.8.md)
- [v3.7](./changelogs/CHANGELOG-v3.7.md)
- [v3.6](./changelogs/CHANGELOG-v3.6.md)
- [v3.5](./changelogs/CHANGELOG-v3.5.md)
- [v3.4](./changelogs/CHANGELOG-v3.4.md)
- [v3.3](./changelogs/CHANGELOG-v3.3.md)
- [v3.2](./changelogs/CHANGELOG-v3.2.md)
- [v3.1](./changelogs/CHANGELOG-v3.1.md)
- [v3.0](./changelogs/CHANGELOG-v3.0.md)
- [v2.2](./changelogs/CHANGELOG-v2.2.md)
- [v2.1](./changelogs/CHANGELOG-v2.1.md)
- [v2.0](./changelogs/CHANGELOG-v2.0.md)
- [v1.2](./changelogs/CHANGELOG-v1.2.md)
- [v1.1](./changelogs/CHANGELOG-v1.1.md)
- [v1.0](./changelogs/CHANGELOG-v1.0.md)
- [v0.*](./changelogs/CHANGELOG-v0.md)

---

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

[Unreleased]: https://github.com/remp2020/remp/compare/5.1.0...master
