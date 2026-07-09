# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- [Segments] Enabled Elasticsearch query logging when Debug mode is enabled.
- [Segments] Fixed low performance of query results processing caused by double unmarshalling of JSON data.
- [Segments] Improved querying performance by executing Elasticsearch scrolls while the previous scrolls are still being processed.
- Fixed seeding of config options, which were included in the migration, but not present in the `ConfigSeeder`. remp/euobserver#268

### [Campaign]

- Added `closed` and `clicked` function between custom JS params to allow correctly handling events. remp/remp#1487
- Added banner dimension option `force_manual_tracking` to force manual events tracking for banners using given dimension. remp/remp#1489
  - When enabled for a dimension, the *Track banner events manually* checkbox is checked and disabled in the banner form, and the flag is enforced on save.

### [Campaign]

- Fixed snippet and collection edit forms breaking when a name contains JavaScript-sensitive characters; such snippets can now be opened and fixed. remp/remp#1405

### [Mailer]

- Added a per-newsletter-type "Exclude emails from search" option that hides a mail type's templates from the email template search. remp/remp#1462
- Fixed a null error in the newsletter list form when no `system` mail type exists. remp/remp#1462
- **IMPORTANT**: Added support for partitioning the `mail_logs` table by month to keep it performant at scale. Requires a manual migration and permanently scheduled commands afterwards — see `Mailer/extensions/mailer-module/docs/MAIL_LOGS_PARTITIONING.md`. remp/remp#1481
  - Added `mail_logs:migrate-to-partitions`, `mail_logs:backfill-partitions`, `mail_logs:prune-partitions` and `mail_logs:seed-partitions` commands.
  - Added a persisted daily stats rollup (`mail_template_direct_stats`) and a `mail_logs_stats_state` cutoff date so template/newsletter statistics stay accurate after old `mail_logs` rows are pruned.
- **BREAKING**: Removed the `hard_bounced_at` column from `mail_logs`, and the matching `hard_bounced_at` filter/response field from the `mail:logs` and `mail:logs-count-per-status` API endpoints. remp/remp#1481
  - Use `dropped_at` instead — bounce events are now recorded there.
- **BREAKING**: Removed `LogsRepository::getNonBatchTemplateStats()`. remp/remp#1481
  - Use `MailTemplateDirectStatsRepository::sumForTemplates()` instead; it reads a daily precomputed rollup (populated by `mail:aggregate-mail-template-stats`) rather than live-scanning `mail_logs`, and no longer exposes a `hard_bounced` key.
- **BREAKING**: Changed `mail:aggregate-mail-template-stats` to take `--date=`/`--from=` options instead of a positional `date` argument. remp/remp#1481
  - Update any crontab/scheduler entries from `mail:aggregate-mail-template-stats YYYY-MM-DD` to `mail:aggregate-mail-template-stats --date=YYYY-MM-DD` (or `--from=YYYY-MM-DD` to recompute/backfill a range, including today).
- **BREAKING**: Dropped all foreign keys to/from `mail_logs` (InnoDB forbids FKs on partitioned tables); its primary key is now composite `(id, created_at)` instead of `id`. remp/remp#1481
  - If you have custom modules/repositories doing implicit Nette DB joins against `mail_logs` (e.g. `->where('mail_template.code', ...)`), either register a `Conventions` service that knows these relations (see `Models/Database/PartitionedConventions.php` for the pattern) or rewrite them as explicit `WHERE` conditions on the FK id column — the default `DiscoveredConventions` FK-based join discovery no longer resolves joins to/from this table.

## Archive

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
