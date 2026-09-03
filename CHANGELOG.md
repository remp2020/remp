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

- Added `bannerClosed`, `bannerClicked`, `bannerShown` function between custom JS params to allow correctly handling banner events. remp/remp#1487
- Added banner dimension option `force_manual_tracking` to force manual events tracking for banners using given dimension. remp/remp#1489
  - When enabled for a dimension, the *Track banner events manually* checkbox is checked and disabled in the banner form, and the flag is enforced on save.
- Fixed snippet and collection edit forms breaking when a name contains JavaScript-sensitive characters; such snippets can now be opened and fixed. remp/remp#1405
- Changed `campaigns:aggregate-stats` command so it no longer aborts the whole run when only *some* campaign banners' stats requests to Beam Segments fail; those banners are skipped, the errors are printed. If *every* banner fails, it still fails loudly (non-zero exit). remp/remp#1491
- Raised the Beam Segments stats client's connect timeout from 1s to 3s, and made `REMP_SEGMENTS_TIMEOUT` the budget for the query alone rather than for connect + query. remp/remp#1491

### [Mailer]

- Added support for WordPress block-editor (Gutenberg) content to the Newsfilter, Napunk Newsfilter, Grafdna and Novydenik Newsfilter generators. remp/remp#1498
- Fixed a PHP 8.5 deprecation (`Non-canonical cast (boolean)`) in the `yesno` Latte/Twig filter, which Tracy escalates to an exception with `error_reporting(E_ALL)`.
- Added a `configuration`/`update` permission check to the Settings (mailer configuration) page, following the existing `batch`/`start` pattern. As with other privileges, it's unrestricted by default; register it via `permissionManager` in `config.local.neon` to require a role for access — see `Mailer/extensions/mailer-module/README.md`'s "Permission management" section.
- Added a per-newsletter-type "Exclude emails from search" option that hides a mail type's templates from the email template search. remp/remp#1462
- Fixed a null error in the newsletter list form when no `system` mail type exists. remp/remp#1462
- **IMPORTANT**: Added support for partitioning the `mail_logs` table by month to keep it performant at scale. Requires a manual migration and permanently scheduled commands afterwards — see `Mailer/extensions/mailer-module/docs/MAIL_LOGS_PARTITIONING.md`. remp/remp#1481
  - Added `mail_logs:migrate-to-partitions`, `mail_logs:backfill-partitions`, `mail_logs:prune-partitions` and `mail_logs:seed-partitions` commands.
  - Added a persisted daily stats rollup (`mail_template_direct_stats`) and a `mail_logs_stats_state` cutoff date so template/newsletter statistics stay accurate after old `mail_logs` rows are pruned.
  - `mail_template_direct_stats` starts out empty, so the migration requires a one-off `mail:aggregate-mail-template-stats --from=<earliest mail_logs date>` backfill run *before* `mail_logs:migrate-to-partitions`, or all pre-migration direct-send statistics (in particular, system/transactional templates) will read as 0 forever. `mail_logs:migrate-to-partitions` now refuses to run until this backfill has happened (`--force` overrides) — see the updated runbook's "Migration execution steps".
  - Added a unique key on `(mail_template_id, date)` to both `mail_template_stats` and `mail_template_direct_stats`, and changed their `upsert()` to a single `INSERT ... ON DUPLICATE KEY UPDATE`. Both tables are read as unbounded lifetime sums, so a duplicate day — which the recommended per-minute + daily `--from` schedule produced on every overlap, since the previous implementation was a read-then-write — permanently inflated reported statistics. The `AddUniqueKeyToTemplateStatsTables` migration removes any pre-existing duplicate rows (keeping the most recently written one) before adding the key.
  - The partitioned schema is built in a `mail_logs_partitioned` shadow table, deliberately *not* the `mail_logs_v2` name used by the older bigint migration — `mail:bigint_migration_cleanup mail_logs` unconditionally drops `mail_logs_v2` and would otherwise destroy an in-flight migration.
  - All four commands bound the metadata-lock wait of every DDL statement they run against the live `mail_logs` (`RENAME TABLE`, `EXCHANGE PARTITION`, `REORGANIZE PARTITION`, `DROP FOREIGN KEY`): each attempt waits at most 5 seconds on a session-only `lock_wait_timeout`, then withdraws its request so blocked application writes drain, and retries for ~10 minutes before failing cleanly. Without this, MySQL's one-year default `lock_wait_timeout` turns an unavailable metadata lock into an indefinite hang that queues every `mail_logs` write behind it and can exhaust `max_connections`. Recommendation: alert on the scheduled commands' exit status — a failed step is now a clean non-zero exit with nothing half-applied, not a stall.
  - `mail_logs:prune-partitions` and `mail_logs:backfill-partitions` swap stage tables in with `EXCHANGE PARTITION`, which MySQL rejects unless the stage table's instant-column attributes match the partitioned table's. Since MySQL 8.0.29 `ADD COLUMN`/`DROP COLUMN` default to `ALGORITHM=INSTANT`, so one such `ALTER` on `mail_logs` permanently breaks both commands (`Non matching attribute 'INSTANT COLUMN(s)' between partition and table`) until the whole table is rebuilt. Recommendation: write every future column change on `mail_logs` with an explicit `ALGORITHM=INPLACE` — see the runbook's "Other behavioral notes" for the one-line repair if it does happen.
  - `mail_logs.created_at`/`updated_at` change from `TIMESTAMP` (UTC-normalised) to `DATETIME` (wall-clock), so `mail_logs:migrate-to-partitions` converts them using its session `time_zone`, which it now pins to PHP's configured zone **by name** rather than to a fixed UTC offset. A fixed offset applies today's DST state to the whole history: on one real 3.7M-row dataset that shifted 1.1M rows by an hour, 219 of them onto the wrong calendar day and 7 into the wrong month partition. Named zones require MySQL's time-zone tables to be populated; the command falls back to the fixed offset with a loud warning if they are not. Recommendation: run `mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql` before the migration — and if the session zone changes between runs, truncate `mail_logs_partitioned` and clear the `mail_logs_partitions_migration_tier_*_last_id` Redis keys first, since the composite `(id, created_at)` primary key makes a re-copy under a different converted timestamp a duplicate id rather than a no-op.
  - `mail_logs:migrate-to-partitions` drops the `mail_log_conversions` → `mail_logs` foreign key *after* the table swap (against the frozen `mail_logs_old`) rather than before it, and its phases are renumbered to a gapless Phase 0–8. An FK drop takes a metadata lock on the parent table that concurrent writes can starve indefinitely, so doing it against the live table risked taking the database down. Recommendation: pause `worker:mail`, `worker:hermes` and the `mail:mailgun-events` cron for the duration of the go-live run — the runbook's new "Locking behaviour" section lists every writer that needs pausing, including the two API endpoints that cannot be stopped by stopping a process.
- **BREAKING**: Removed the `hard_bounced_at` column from `mail_logs`, and the matching `hard_bounced_at` filter/response field from the `mail:logs` and `mail:logs-count-per-status` API endpoints. remp/remp#1481
  - Use `dropped_at` instead — bounce events are now recorded there.
- **BREAKING**: Removed `LogsRepository::getNonBatchTemplateStats()`. remp/remp#1481
  - Use `MailTemplateDirectStatsRepository::sumForTemplates()` instead; it reads a daily precomputed rollup (populated by `mail:aggregate-mail-template-stats`) rather than live-scanning `mail_logs`, and no longer exposes a `hard_bounced` key.
- **BREAKING**: Changed `mail:aggregate-mail-template-stats` to take `--date=`/`--from=` options instead of a positional `date` argument (including relative values like `today`/`tomorrow`), and changed its no-option default from "yesterday" to "yesterday and today". remp/remp#1481
  - Update any crontab/scheduler entries: `mail:aggregate-mail-template-stats YYYY-MM-DD` becomes `mail:aggregate-mail-template-stats --date=YYYY-MM-DD` (or `--from=YYYY-MM-DD` to recompute/backfill a range, including today); a `today`/`tomorrow` pair of entries becomes a single bare `mail:aggregate-mail-template-stats` entry, since the new default already covers both. Add a second, daily entry with `--from=$(date -d '-30 days' +%Y-%m-%d)` so opens/clicks/conversions that arrive after a day was first aggregated are still picked up — see the updated runbook's "Permanently scheduled" section.
- **BREAKING**: `mail:aggregate-mail-template-stats` now refuses a `--date=`/clamps a `--from=` that reaches below the active `mail_logs_stats_state` cutoff date, since `mail_logs` no longer holds complete data before it once pruning has run; pass `--force` for a deliberate override. remp/remp#1481
  - Only affects installations that have completed the `mail_logs` partitioning migration and started pruning; no effect otherwise (the cutoff date stays unset).
- **BREAKING**: Dropped all foreign keys to/from `mail_logs` (InnoDB forbids FKs on partitioned tables); its primary key is now composite `(id, created_at)` instead of `id`. remp/remp#1481
  - If you have custom modules/repositories doing implicit Nette DB joins against `mail_logs` (e.g. `->where('mail_template.code', ...)`), either register a `Conventions` service that knows these relations (see `Models/Database/PartitionedConventions.php` for the pattern) or rewrite them as explicit `WHERE` conditions on the FK id column — the default `DiscoveredConventions` FK-based join discovery no longer resolves joins to/from this table.
- **BREAKING**: Removed the `mail:migrate-mail-logs-and-conversions` command (the second step of the `mail_logs`/`mail_log_conversions` bigint migration for installations older than 1.1.0), along with its `logConversionsRepository` dual-write configuration. remp/remp#1481
  - If that migration never completed on your installation — `mail_logs.user_id` missing, or a leftover `mail_logs_v2` / `mail_log_conversions_v2` / `mail_logs_old` table — complete it on a pre-5.2.0 release before upgrading. The partitioning migration hard-requires it (it copies `mail_logs.user_id`), so `CreatePartitionedMailLogsTable` and `mail_logs:migrate-to-partitions` both abort with instructions instead of failing halfway through.
- **BREAKING**: `mail:bigint_migration_cleanup mail_logs` now refuses to drop `mail_logs_old` while `mail_logs_backfill_state` still has `pending` month partitions. remp/remp#1481
  - `mail_logs_old` is the only source `mail_logs:backfill-partitions` can read historical months from, so dropping it early loses them irrecoverably. Finish the backfill first, or drop the table manually if you have deliberately abandoned it.

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
