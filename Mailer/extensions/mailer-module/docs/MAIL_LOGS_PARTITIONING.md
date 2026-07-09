# `mail_logs` partitioning migration

`mail_logs` can grow very large over time, which eventually makes it slow to query and expensive to keep fully indexed. This migration converts it into a table partitioned by month (`RANGE COLUMNS(created_at)`), so that:

- old data can be dropped instantly (by exchanging/dropping whole partitions) instead of with slow row-by-row `DELETE`s,
- queries scoped to a recent time range can prune to a single partition instead of scanning the whole table,
- "important" mail (system mail, and mail types above a configurable priority) is kept forever, while everything else is retained only up to a configurable cutoff date.

## What changes

- `mail_logs` becomes `PARTITION BY RANGE COLUMNS (created_at)`, with one partition per calendar month plus a catch-all `p_max` partition for anything beyond the currently materialized months.
- The primary key changes from `id` to composite `(id, created_at)` — required by MySQL for `RANGE COLUMNS` partitioning on `created_at`.
- All foreign keys to/from `mail_logs` are dropped — InnoDB forbids foreign keys on partitioned tables. Nette DB join resolution for the affected relations (`mail_logs`↔`mail_templates`, `mail_logs`↔`mail_jobs`, `mail_logs`↔`mail_job_batch`, `mail_log_conversions`↔`mail_logs`) is instead handled by `Remp\MailerModule\Models\Database\PartitionedConventions`, registered as the app's `database.default.conventions` service.
- The `hard_bounced_at` column is removed; bounce events are now recorded via `dropped_at`.
- Reported statistics (template/newsletter stats) are switched to a persisted daily rollup (`mail_template_stats` / `mail_template_direct_stats`, kept up to date by `mail:aggregate-mail-template-stats`) instead of live-querying `mail_logs`, so numbers don't change retroactively once old rows are pruned. See [`CHANGELOG.md`](../../../../CHANGELOG.md) for the full list of breaking changes this introduces.

**Core guarantee going forward:** system mail (`mail_type_categories.code = 'system'`, or `mail_types.code` in `system`/`system_optional`) and mail types with `priority` above a configurable threshold are kept indefinitely. Everything else is only retained up to a cutoff date that moves forward over time as pruning runs.

> **Archive before you prune.** Once a cutoff date is applied (during backfill or ongoing pruning), non-priority `mail_logs` rows older than that cutoff are deleted and cannot be recovered from `mail_logs` — only from `mail_logs_old` (while it still exists) or your own backups.

## Decisions to make before starting

- **Priority threshold** (`--priority-threshold`, default `1000`). Mail types with `priority` strictly greater than this value are treated as "priority" and kept forever, alongside system mail. Inspect the actual distribution in your installation before picking a value:
  ```sql
  SELECT priority, COUNT(*) FROM mail_types GROUP BY priority ORDER BY priority DESC;
  ```
- **Initial cutoff date.** `mail_logs:migrate-to-partitions` sets this automatically to the first day of the previous month (the "live window" it migrates in full at go-live). It is not directly configurable — plan the rest of the migration around it.
- **Backfill cutoff date** (optional `--cutoff-date` on `mail_logs:backfill-partitions`). Decide whether historical newsletter months should be backfilled in full, or only their system/priority rows should be kept (with the rest of that month's non-priority mail discarded immediately instead of "topped up" later).
  > This choice is **sticky per month**: once a month is marked `done` under a cutoff, re-running backfill later with no cutoff (or a wider one) will **not** go back and restore the rows that were skipped.
- **Ongoing retention window.** How far back `mail_logs:prune-partitions --cutoff-date=...` should reach every time it runs (e.g. "always keep the last 13 months of full non-priority history"). This is a rolling decision — the actual date passed to `--cutoff-date` must be recomputed relative to "now" on every scheduled run, not hardcoded once.
- **Archival strategy.** Decide whether/how `mail_logs_old` (and any pruned data) should be archived — e.g. a `mysqldump` to cold storage — before it is dropped or pruning starts removing rows for good.

## Pre-migration steps

1. Confirm the following Phinx migrations are present and unapplied: `CreatePartitionedMailLogsTable`, `CreateMailLogsBackfillStateTable`, `CreateMailLogsStatsStateTable`, `CreateMailTemplateDirectStatsTable`.
2. Take a full database backup/snapshot — this migration renames tables and drops foreign key constraints.
3. Decide the priority threshold and cutoff strategy from the previous section before running anything.
4. Make sure there's enough free disk space for a full duplicate of `mail_logs`: during migration and backfill, `mail_logs_v2`/`mail_logs_old` coexist with the live `mail_logs` table.

## Migration execution steps

1. **Run the Phinx migrations.** This creates the partitioned shadow table `mail_logs_v2` (with partitions pre-built up to 6 months ahead) plus the `mail_logs_backfill_state`, `mail_logs_stats_state` and `mail_template_direct_stats` tables. If `mail_logs` is empty, the table is converted in place and the remaining steps are not needed.
2. **Run `mail_logs:migrate-to-partitions [--priority-threshold=N]` once.** This is a single go-live event, not something you repeat. It migrates all system/priority mail (regardless of age) plus the complete current and previous calendar month, then atomically renames `mail_logs` → `mail_logs_old` and `mail_logs_v2` → `mail_logs`.
3. **Immediately clear the application cache and opcache on every web/worker node.** The primary key shape changed to `(id, created_at)`; until Nette's database structure cache is rebuilt, primary-key updates only filter on `WHERE id = ?` and scan every partition instead of pruning to one. Verify afterwards with `EXPLAIN PARTITIONS` on a single-row primary-key update.
4. **Run `mail_logs:backfill-partitions` repeatedly** (optionally with `--limit`, `--month`, and `--cutoff-date`/`--priority-threshold` if you decided on a backfill cutoff) until `mail_logs_backfill_state` has no rows left with `status = 'pending'`. This fills in the historical newsletter months that step 2 deliberately left out.
5. **Once every month reports done**, archive `mail_logs_old` per the strategy you decided on, then drop it with `mail:bigint_migration_cleanup mail_logs_old`. As with this module's other bigint migrations, wait at least 2 weeks after the backfill completes before dropping it, in case an issue emerges and you need the original data.

## Permanently scheduled

Two commands need to run indefinitely from this point on, in addition to the existing `mail:process-job` / `mail:job-stats` crontab entries documented under [Scheduled events](../README.md#scheduled-events):

```
# Keep 6 months of future partitions materialized ahead of time.
0 3 1 * * php /home/remp/workspace/remp/Mailer/bin/command.php mail_logs:seed-partitions --months=6

# Prune non-priority mail_logs data older than the retention window (example: 13 months).
0 4 1 * * php /home/remp/workspace/remp/Mailer/bin/command.php mail_logs:prune-partitions --cutoff-date=$(date -d '-13 months' +\%Y-\%m-01) --priority-threshold=1000
```

- **`mail_logs:seed-partitions`** — run monthly or more often. It ensures partitions exist for the next `--months` (default 6) months; if this lapses, nothing breaks immediately, new rows simply fall into the catch-all `p_max` partition until the command runs again.
- **`mail_logs:prune-partitions`** — run monthly. It deletes non-priority `mail_logs`/`mail_log_conversions` rows older than `--cutoff-date` by exchanging each affected partition, then advances the stats cutoff (`mail_logs_stats_state`) so persisted statistics stay consistent with what was actually deleted. The cutoff date must be computed relative to "now" at run time (as in the example above), and `--priority-threshold` should match the value used at go-live/backfill.

## Other behavioral notes

- `LogsRepository::findBySenderId()` / `findAllBySenderId()` now accept an optional `$since` bound, and callers that look up recent sender IDs (Mailgun event handling, CRM email validation) pass a 14-day window by default. If you need to look up very old sender IDs, be aware one path falls back to a full scan and the other does not.
