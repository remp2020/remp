<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Ensures that the partitioned mail_logs table has a monthly partition for every
 * month up to N months into the future.
 *
 * Idempotent: already-existing partitions are detected via information_schema and
 * skipped; only missing months are created by REORGANIZEing the trailing p_max
 * partition.  The p_max catch-all is always preserved so no insert can ever fail
 * due to a missing partition.
 *
 * Run this command after the initial migration and then monthly (or more
 * frequently) to keep the horizon extended — the table only ships with a few
 * months of pre-seeded partitions (see CreatePartitionedMailLogsTable), so a
 * lapsed schedule is not immediately fatal: inserts still succeed by falling into
 * p_max, they just land in the catch-all partition instead of a dedicated month
 * until this command is run again.
 *
 * Usage:
 *   php bin/command.php mail_logs:seed-partitions
 *   php bin/command.php mail_logs:seed-partitions --months=12
 */
class SeedMailLogsPartitionsCommand extends Command
{
    use MailLogsPartitioningHelpersTrait;

    public const COMMAND_NAME = 'mail_logs:seed-partitions';

    public function __construct(
        private Explorer $database,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Ensures mail_logs has monthly partitions up to N months into the future. '
                . 'Idempotent; safe to re-run at any time.'
            )
            ->addOption(
                'months',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of months of future partitions to guarantee.',
                6
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $months = (int) $input->getOption('months');
        if ($months < 1 || $months > 120) {
            $output->writeln('<error>--months must be between 1 and 120.</error>');
            return Command::FAILURE;
        }

        if (!$this->isPartitioned()) {
            $output->writeln('<error>`' . self::TABLE . '` is not a partitioned table.</error>');
            $output->writeln('<error>Run the CreatePartitionedMailLogsTable Phinx migration and</error>');
            $output->writeln('<error>MigrateMailLogsToPartitionsCommand first.</error>');
            return Command::FAILURE;
        }

        $existing = $this->loadExistingPartitions();

        if (!isset($existing['p_max'])) {
            $output->writeln('<error>Catch-all partition `p_max` is missing — table may be misconfigured.</error>');
            return Command::FAILURE;
        }

        // Compute the target set: every month from now through now+N months.
        $target = $this->buildTargetMonths($months);
        $missing = array_diff($target, array_keys($existing));

        if (empty($missing)) {
            $output->writeln(sprintf(
                '<info>All partitions up to %d months ahead already exist. Nothing to do.</info>',
                $months
            ));
            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            'Creating %d missing partition(s) …',
            count($missing)
        ));

        // REORGANIZE PARTITION below rewrites p_max and does NOT permit concurrent DML, so its
        // cost is proportional to how many rows have accumulated there — which is exactly what
        // a lapsed schedule causes. Report it before the ALTER, so an operator watching a long
        // write stall knows why (and can decide to pause the writers first).
        $pMaxRows = (int) $this->database
            ->query("SELECT COUNT(*) AS cnt FROM `" . self::TABLE . "` PARTITION (`p_max`)")
            ->fetch()
            ->cnt;
        if ($pMaxRows > 0) {
            $output->writeln(sprintf(
                '<comment>`p_max` currently holds %d row(s); each REORGANIZE below rewrites them and blocks writes to '
                . '`%s` for its duration.</comment>',
                $pMaxRows,
                self::TABLE
            ));
        }

        // We must add partitions in chronological order (each REORGANIZE splits the
        // current p_max into one new named month + the new p_max tail).
        sort($missing);
        foreach ($missing as $partitionName) {
            // Derive the upper-bound date from the partition name (p_YYYY_MM).
            $upperBound = $this->upperBoundForPartitionName($partitionName);
            if ($upperBound === null) {
                $output->writeln("<comment>  Skipping unrecognised partition name: {$partitionName}</comment>");
                continue;
            }

            $output->writeln("  Creating `{$partitionName}` (< {$upperBound}) … ");

            // Bounded metadata-lock wait: this runs unattended on a monthly cron against the
            // live table, where an unbounded wait would queue every mail_logs write behind it
            // and exhaust the connection pool. See executeDdlWithBoundedLockWait().
            //
            // Giving up is safe here: each REORGANIZE is independent and the p_max catch-all is
            // always preserved, so a partition that could not be created just means inserts for
            // that month keep landing in p_max until the next run.
            try {
                $this->executeDdlWithBoundedLockWait(
                    $output,
                    "REORGANIZE PARTITION for `{$partitionName}`",
                    "
                        ALTER TABLE `" . self::TABLE . "`
                        REORGANIZE PARTITION `p_max` INTO (
                            PARTITION `{$partitionName}` VALUES LESS THAN ('{$upperBound}'),
                            PARTITION `p_max`            VALUES LESS THAN (MAXVALUE)
                        )
                    "
                );
            } catch (\RuntimeException $e) {
                // Covers both the exhausted lock wait and a rejected ALTER — Nette's DriverException
                // is a RuntimeException too, and neither leaves anything half-applied.
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $output->writeln('<error>Partitions after `' . $partitionName . '` were not created either; inserts keep '
                    . 'falling into `p_max` until this command is run again.</error>');
                return Command::FAILURE;
            }

            $output->writeln('  done.');
        }

        $output->writeln('');
        $output->writeln('<info>Partition seeding complete.</info>');

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns the names of all existing partitions keyed by partition name.
     *
     * @return array<string, string>  name => description (the VALUES LESS THAN value)
     */
    private function loadExistingPartitions(): array
    {
        $rows = $this->database->query("
            SELECT PARTITION_NAME, PARTITION_DESCRIPTION
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND PARTITION_NAME IS NOT NULL
            ORDER BY PARTITION_ORDINAL_POSITION
        ", self::TABLE)->fetchPairs('PARTITION_NAME', 'PARTITION_DESCRIPTION');

        return $rows ?: [];
    }

    /**
     * Returns an array of partition names (p_YYYY_MM) that should exist to cover
     * every month from the current month through now + $months months.
     *
     * @return string[]
     */
    private function buildTargetMonths(int $months): array
    {
        $current = new \DateTime('first day of this month 00:00:00');
        $end = (clone $current)->modify("+{$months} months");

        $names = [];
        while ($current < $end) {
            $names[] = $this->partitionNameForMonth($current);
            $current->modify('+1 month');
        }

        return $names;
    }

    /**
     * Derives the RANGE COLUMNS upper-bound string from a partition name like p_2031_03.
     * Returns null for names that do not follow the p_YYYY_MM convention.
     */
    private function upperBoundForPartitionName(string $name): ?string
    {
        if (!$this->isPartitionName($name)) {
            return null;
        }

        // Upper bound is the first day of the NEXT month, i.e. monthEnd.
        [, $monthEnd] = $this->monthBoundsForPartitionName($name);

        return $monthEnd->format('Y-m-d 00:00:00');
    }
}
