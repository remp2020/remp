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
  - This is **not symmetric**: batch-send counters (`mail_job_batch_templates`) are populated by the pre-existing `mail:job-stats` command and are sealed automatically at go-live. `mail_template_direct_stats` is a brand-new table and starts **empty** — nothing in the migration commands fills it. Since it is read as an unbounded lifetime sum (`MailTemplateDirectStatsRepository::sumForTemplates()`), any day left unaggregated reports `0` direct-send stats forever, most visibly for system/transactional templates (which have no batch sends at all to fall back on). You must backfill it yourself — see step 2 of [Migration execution steps](#migration-execution-steps) below.

**Core guarantee going forward:** system mail (`mail_type_categories.code = 'system'`, or `mail_types.code` in `system`/`system_optional`) and mail types with `priority` above a configurable threshold are kept indefinitely. Everything else is only retained up to a cutoff date that moves forward over time as pruning runs.

> **Archive before you prune.** Once a cutoff date is applied (during backfill or ongoing pruning), non-priority `mail_logs` rows older than that cutoff are deleted and cannot be recovered from `mail_logs` — only from `mail_logs_old` (while it still exists) or your own backups.

## Decisions to make before starting

- **Priority threshold** (`--priority-threshold`, default `1000`). Mail types with `priority` strictly greater than this value are treated as "priority" and kept forever, alongside system mail. Inspect the actual distribution in your installation before picking a value:
  ```sql
  SELECT priority, COUNT(*) FROM mail_types GROUP BY priority ORDER BY priority DESC;
  ```
  > Pick this **once** and use the same value for go-live, backfill and every scheduled prune. Each of those commands builds its keep-set from the threshold it was given and `EXCHANGE PARTITION`s the result in, replacing the partition wholesale — so pruning under a higher threshold than a month was migrated under deletes live system/priority rows that were supposed to be kept forever, with no warning. Record the chosen value alongside the crontab entry.
- **Initial cutoff date.** `mail_logs:migrate-to-partitions` sets this automatically to the first day of the previous month (the "live window" it migrates in full at go-live). It is not directly configurable — plan the rest of the migration around it.
- **Backfill cutoff date** (optional `--cutoff-date` on `mail_logs:backfill-partitions`). Decide whether historical newsletter months should be backfilled in full, or only their system/priority rows should be kept (with the rest of that month's non-priority mail discarded immediately instead of "topped up" later).
  > This choice is **sticky per month**: once a month is marked `done` under a cutoff, re-running backfill later with no cutoff (or a wider one) will **not** go back and restore the rows that were skipped.
- **Ongoing retention window.** How far back `mail_logs:prune-partitions --cutoff-date=...` should reach every time it runs (e.g. "always keep the last 13 months of full non-priority history"). This is a rolling decision — the actual date passed to `--cutoff-date` must be recomputed relative to "now" on every scheduled run, not hardcoded once.
- **Archival strategy.** Decide whether/how `mail_logs_old` (and any pruned data) should be archived — e.g. a `mysqldump` to cold storage — before it is dropped or pruning starts removing rows for good.
- **How far back to aggregate direct-send statistics.** `mail_template_direct_stats` is read as an unbounded lifetime sum, so any day not aggregated is permanently missing from template/job statistics. Default recommendation: everything, starting from `SELECT MIN(created_at) FROM mail_logs`. The cost is one `GROUP BY mail_template_id` query (plus one conversions query) per day, so a multi-year history takes a while — plan the backfill run's duration into your migration window.

## Pre-migration steps

1. **Confirm the older bigint migration is fully finished.** Partitioning hard-requires it: the copy queries select `mail_logs.user_id`, a column that only exists afterwards. Check that `mail_logs.user_id` exists and that none of `mail_logs_v2`, `mail_log_conversions_v2`, `mail_logs_old` are left over (`mail:bigint_migration_cleanup mail_logs` / `mail_log_conversions` archives and drops them). `CreatePartitionedMailLogsTable` aborts with instructions if any of this is unmet, and `mail_logs:migrate-to-partitions` refuses to start — a leftover `mail_logs_old` in particular would make the go-live `RENAME` fail *after* the point of no return.
   > The command that performs that migration, `mail:migrate-mail-logs-and-conversions`, was **removed in 5.2.0**. If it never completed on your installation, finish it on a pre-5.2.0 release before upgrading — see [Mail logs migration](../README.md#mail-logs-migration-version--110) in the README.
2. Confirm the following Phinx migrations are present and unapplied: `CreatePartitionedMailLogsTable`, `CreateMailLogsBackfillStateTable`, `CreateMailLogsStatsStateTable`, `CreateMailTemplateDirectStatsTable`, `AddUniqueKeyToTemplateStatsTables`.
3. Take a full database backup/snapshot — this migration renames tables and drops foreign key constraints.
4. Decide the priority threshold and cutoff strategy from the previous section before running anything.
5. Make sure there's enough free disk space for a full duplicate of `mail_logs`: during migration and backfill, `mail_logs_partitioned`/`mail_logs_old` coexist with the live `mail_logs` table.
6. Update your `mail:aggregate-mail-template-stats` crontab entry as part of this deploy. The positional `date` argument was removed — including relative values like `today`/`tomorrow` — in favor of `--date=`/`--from=` options, and the no-option default now covers **yesterday and today** instead of yesterday only (see [Permanently scheduled](#permanently-scheduled)). Any pre-existing `... mail:aggregate-mail-template-stats today` / `... tomorrow` pair of cron entries must be replaced, or they will silently no-op.
7. Record `SELECT MIN(created_at) FROM mail_logs;` — you'll need this date for the statistics backfill in step 2 of the next section.
8. Optionally pause the `mail:aggregate-mail-template-stats` cron while the (potentially long) backfill run in the next section is in progress. This is a load consideration only, not a correctness one.

## Locking behaviour

Partitioning DDL (`RENAME TABLE`, `EXCHANGE PARTITION`, `REORGANIZE PARTITION`, `DROP FOREIGN KEY`) needs a **table-level metadata lock** on `mail_logs`. Metadata locks are never partition-scoped.

Two MySQL defaults make this dangerous, and they are the reason all four commands behave the way described below:

- **`lock_wait_timeout` defaults to 31536000 seconds — a full year** (not to be confused with `innodb_lock_wait_timeout`'s 50 seconds). DDL that cannot get its metadata lock therefore does not fail, it *hangs*.
- **A pending metadata-lock request blocks the queue behind it.** Every subsequent application `INSERT`/`UPDATE` on `mail_logs` waits too, each holding a connection — so a metadata-only `ALTER` that does microseconds of actual work can exhaust `max_connections` and take the whole database down.

All four commands (`mail_logs:migrate-to-partitions`, `mail_logs:backfill-partitions`, `mail_logs:prune-partitions`, `mail_logs:seed-partitions`) therefore route every such statement through a **bounded lock wait**: each attempt waits at most 5 seconds, then withdraws its request so the queued application writes drain immediately, and retries.

If a command does give up, the DDL step in question simply did not happen — nothing is left half-applied. Pause the writers (below) and re-run.

The commands do not name the blocking session: identifying it needs `performance_schema.metadata_locks`, which the application database user normally cannot read. If you have a privileged account and want to know who is holding the lock, run this yourself:

```sql
SELECT ml.OBJECT_NAME, ml.LOCK_TYPE, ml.LOCK_STATUS, t.PROCESSLIST_ID,
       t.PROCESSLIST_TIME, t.PROCESSLIST_STATE, LEFT(t.PROCESSLIST_INFO, 200) AS info,
       trx.trx_started, trx.trx_state
  FROM performance_schema.metadata_locks ml
  JOIN performance_schema.threads t ON t.THREAD_ID = ml.OWNER_THREAD_ID
  LEFT JOIN information_schema.innodb_trx trx ON trx.trx_mysql_thread_id = t.PROCESSLIST_ID
 WHERE ml.OBJECT_SCHEMA = DATABASE()
   AND ml.OBJECT_NAME IN ('mail_logs', 'mail_log_conversions')
   AND ml.LOCK_STATUS = 'GRANTED'
   AND t.PROCESSLIST_ID <> CONNECTION_ID()
 ORDER BY t.PROCESSLIST_TIME DESC;
```

A cruder fallback that works with fewer privileges is `SHOW FULL PROCESSLIST` — look for `Waiting for table metadata lock` and for the oldest session in `Sleep` inside a transaction. Pausing the writers is the fix in either case; do not `KILL` an application transaction to force the DDL through unless you know what that transaction was doing.

### Pausing the writers before go-live

**Strongly recommended for `mail_logs:migrate-to-partitions`.** The bounded retry loop is designed to succeed against live traffic, but with the writers paused the go-live `RENAME` lands on the first attempt instead of possibly retrying for minutes.

Stop these for the duration of the run — how you do that depends on your deployment (systemd units, supervisor, container orchestration, crontab), so the list below is what needs to stop, not how:

- **`worker:mail`** 
- **`worker:hermes`** 
- Any cron of your own that sends mail through Mailer's API or commands.

## Migration execution steps

1. **Run the Phinx migrations.** This creates the partitioned shadow table `mail_logs_partitioned` (with partitions pre-built up to 6 months ahead) plus the `mail_logs_backfill_state`, `mail_logs_stats_state` and `mail_template_direct_stats` tables. If `mail_logs` is empty, the table is converted in place and the remaining steps are not needed.
2. **Backfill the statistics rollups while `mail_logs` is still complete.**
   ```
   php bin/command.php mail:aggregate-mail-template-stats --from=YYYY-MM-DD   # the MIN(created_at) date from the previous section
   ```
   This fills `mail_template_direct_stats` for all history and refreshes `mail_template_stats` (including its new `converted` column). It must run here — after the Phinx migrations created the table, but before go-live — because this is the last point at which `mail_logs` holds every row, and the last point at which no stats cutoff date exists to clamp the range. Step 3 below refuses to start until this is done (`--force` overrides).

   The run is idempotent and resumable: a day with no matching rows prints `OK! (no data)` and writes nothing, so existing rollup rows are never zeroed. `--from` always runs through today inclusive.

   Verify before moving on: `SELECT MIN(date), MAX(date), COUNT(*) FROM mail_template_direct_stats;` should span the full history, and a direct-only (system/transactional) template's detail page should show non-zero sent/delivered/opened.

3. **Run `mail_logs:migrate-to-partitions [--priority-threshold=N]` once.** This is a single go-live event, not something you repeat. It migrates all system/priority mail (regardless of age) plus the complete current and previous calendar month, then atomically renames `mail_logs` → `mail_logs_old` and `mail_logs_partitioned` → `mail_logs`. It refuses to run if `mail_template_direct_stats` looks unbackfilled (step 2 above) unless you pass `--force`, or if `mail_logs_old`/`mail_logs_v2` already exists (see pre-migration step 1).
   > **Pause the writers first** — see [Pausing the writers before go-live](#pausing-the-writers-before-go-live). The command is safe to run against live traffic, but the swap acquires an exclusive metadata lock on `mail_logs` and lands immediately on a quiet table.
   >
   > The run prints progress as **Phase 0 through Phase 8**. It is resumable: re-run it if it is interrupted before the swap (Phase 6), and it picks up its cursors, re-enables dual-writes, and keeps the original start time so nothing written in between is missed. After the swap succeeded, do *not* re-run it — the preflight refuses anyway, because `mail_logs_old` then exists.
4. **Immediately clear the application cache and opcache on every web/worker node.** The swapped-in table has a different column list (no `hard_bounced_at`), charset and primary key than the one Nette cached in its database structure; until that cache is rebuilt, queries are still built against the old shape and can fail with `Unknown column`.
   > Don't expect primary-key updates to prune to a single partition, before or after the cache rebuild. `PartitionedConventions::getPrimary()` deliberately reports `id` (not the composite `(id, created_at)`) — returning the composite would break every `ActiveRow`/`wherePrimary()` caller — so `wherePrimary()` updates always emit `WHERE id = ?` and always scan every partition by design. Partition pruning applies to queries that filter on `created_at`.
5. **Run `mail_logs:backfill-partitions`** (optionally repeatedly with `--limit`, `--month`, and `--cutoff-date`/`--priority-threshold` if you decided on a backfill cutoff) until `mail_logs_backfill_state` has no rows left with `status = 'pending'`. This fills in the historical newsletter months that step 3 deliberately left out.
6. **Once every month reports done**, archive `mail_logs_old` per the strategy you decided on, then drop it with `mail:bigint_migration_cleanup mail_logs` (the command takes the *base* table name and drops `mail_logs_old` + `mail_logs_v2`). As with this module's other bigint migrations, wait at least 2 weeks after the backfill completes before dropping it, in case an issue emerges and you need the original data.
   > The command refuses to run while `mail_logs_backfill_state` still has `pending` rows: `mail_logs_old` is the only source the backfill can read from, so dropping it early loses those months irrecoverably. If you have deliberately abandoned the backfill, drop the table manually.

## Permanently scheduled

Both scheduled commands take a metadata lock on the live `mail_logs` and can therefore exit non-zero when they cannot get one in time — see [Locking behaviour](#locking-behaviour) for what that means and what it leaves behind (nothing half-applied; a re-run redoes the step). Alert on their exit status rather than assuming a silent cron is a successful one.

`mail_logs:seed-partitions` and `mail_logs:prune-partitions` need to run indefinitely from this point on, in addition to the existing `mail:process-job` / `mail:job-stats` crontab entries documented under [Scheduled events](../README.md#scheduled-events) — and `mail:aggregate-mail-template-stats`'s existing schedule needs to change:

```
# Keep yesterday's and today's stats rollups current (the only source the UI reads from).
# Both aggregation entries share one lock: the per-minute run can outlast a minute on a large
# installation, and at 02:00 it overlaps the trailing-window run below by construction.
* * * * * flock -n /var/lock/mailer-aggregate-stats.lock php /home/remp/workspace/remp/Mailer/bin/command.php mail:aggregate-mail-template-stats

# Re-aggregate the last 30 days once a day, so opens/clicks/conversions that arrive after
# a day was first sealed still land on the day the mail was actually sent.
0 2 * * * flock /var/lock/mailer-aggregate-stats.lock php /home/remp/workspace/remp/Mailer/bin/command.php mail:aggregate-mail-template-stats --from=$(date -d '-30 days' +\%Y-\%m-\%d)

# Keep 6 months of future partitions materialized ahead of time.
0 3 1 * * php /home/remp/workspace/remp/Mailer/bin/command.php mail_logs:seed-partitions --months=6

# Prune non-priority mail_logs data older than the retention window (example: 13 months).
0 4 1 * * php /home/remp/workspace/remp/Mailer/bin/command.php mail_logs:prune-partitions --cutoff-date=$(date -d '-13 months' +\%Y-\%m-01) --priority-threshold=1000
```

- **`mail:aggregate-mail-template-stats`** — required, not optional, once statistics are read from the persisted rollup rather than live `mail_logs`.
  - With no options it aggregates **yesterday and today**: yesterday keeps absorbing late-arriving Mailgun webhook events, today keeps template/job detail current through the day. This replaces any pre-existing separate `today`/`tomorrow` crontab entries (see [Pre-migration steps](#pre-migration-steps)).
  - The daily `--from=-30 days` run re-aggregates a trailing window once a day, since opens/clicks/conversions keep arriving well after a day's stats were first computed by the per-minute run above — a yesterday+today-only schedule would freeze each day's counters permanently a couple of days after sending. The window is clamped to the active `mail_logs_stats_state` cutoff date automatically, so it can never recompute a period whose non-priority `mail_logs` rows have already been pruned; keep it comfortably inside your retention window (30 days against a 13-month retention window is never close).
  - Both entries recompute the same days on purpose, and the unique key on `(mail_template_id, date)` makes overlapping runs merely wasteful rather than corrupting. The `flock` above is what keeps them from piling up on a busy installation.
- **`mail_logs:seed-partitions`** — run monthly or more often. It ensures partitions exist for the next `--months` (default 6) months.
  > A lapse here is **not** harmless, and this is the riskiest of the recurring commands. Rows for unmaterialized months land in the catch-all `p_max` partition, and getting them back into per-month partitions afterwards needs `ALTER TABLE mail_logs REORGANIZE PARTITION p_max INTO (...)` — which, unlike `EXCHANGE PARTITION`, **does not permit concurrent DML at all**: it blocks every write to the whole of `mail_logs` for as long as it takes to rewrite what has accumulated in `p_max`. That is the exact heavy operation partitioning exists to avoid, and it scales with how long the lapse lasted. Until it runs, `mail_logs:prune-partitions` cannot prune the affected months either: it works by exchanging whole month partitions, and those months have none of their own. The command reports the `p_max` row count before each `REORGANIZE` so you can see the cost coming (and pause the writers if it is large). Monitor this cron like any other required one.
- **`mail_logs:prune-partitions`** — run monthly. It deletes non-priority `mail_logs`/`mail_log_conversions` rows older than `--cutoff-date` by exchanging each affected partition, then advances the stats cutoff (`mail_logs_stats_state`) so persisted statistics stay consistent with what was actually deleted. The cutoff date must be computed relative to "now" at run time (as in the example above).
  > **`--priority-threshold` must match the value used at go-live and backfill.** It is not a filter over what a previous run kept — each run builds a fresh keep-set from the *current* threshold and `EXCHANGE PARTITION`s it in, replacing the partition wholesale. Pruning a month with a higher threshold than it was migrated under therefore silently deletes live system/priority rows that were meant to be kept forever. Pin the value in the crontab entry and treat changing it as a deliberate retention decision, not a tuning knob.

## Other behavioral notes

- **Every future column change on the partitioned `mail_logs` must be written with an explicit `ALGORITHM=INPLACE`.** Since MySQL 8.0.29 `ADD COLUMN`/`DROP COLUMN` default to `ALGORITHM=INSTANT`, which increments the table's instant-column row-version counter. `mail_logs:prune-partitions` and `mail_logs:backfill-partitions` both work by building a stage table with `CREATE TABLE … LIKE mail_logs` and swapping it in with `EXCHANGE PARTITION`, and MySQL rejects that exchange unless the two tables' instant-column attributes match (error 1731, `Non matching attribute 'INSTANT COLUMN(s)' between partition and table`) — a fresh `CREATE TABLE … LIKE` always starts at zero. So a single instant `ALTER` permanently breaks both commands, including the unattended monthly prune cron. It fails cleanly with nothing half-applied, and the repair is to rebuild the whole table once:
  ```sql
  ALTER TABLE mail_logs FORCE, ALGORITHM=INPLACE, LOCK=NONE;
  ```
  Verify with `SELECT NAME, TOTAL_ROW_VERSIONS FROM information_schema.INNODB_TABLES WHERE NAME LIKE CONCAT(DATABASE(), '/mail_logs#p#%');` — every partition must read `0`. Rebuilding individual partitions does **not** clear the counter (it lives on the table definition, not the partition), so this is a full rebuild of every partition and belongs in a maintenance window. Prefer avoiding it by pinning `ALGORITHM=INPLACE` on the `ALTER` in the first place.
- `mail_logs.updated_at` no longer carries `ON UPDATE CURRENT_TIMESTAMP` (the pre-migration column on long-lived installations does, as a leftover of the original `TIMESTAMP` definition — `RANGE COLUMNS` cannot partition on `TIMESTAMP`, so both timestamp columns are now plain `DATETIME`). Application writes are unaffected: `LogsRepository::update()` sets `updated_at` explicitly. But any raw-SQL writer of your own that relied on the column maintaining itself must now set it too — and it matters, because the go-live and backfill catch-up passes use `updated_at` to detect rows that changed mid-migration.
- `LogsRepository::findBySenderId()` / `findAllBySenderId()` now accept an optional `$since` bound, and callers that look up recent sender IDs (Mailgun event handling, CRM email validation) pass a 14-day window by default. If you need to look up very old sender IDs, be aware one path falls back to a full scan and the other does not.
