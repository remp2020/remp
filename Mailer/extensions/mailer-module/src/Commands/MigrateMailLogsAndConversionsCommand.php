<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\EnvironmentConfig;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;
use Remp\MailerModule\Repositories\LogConversionsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateMailLogsAndConversionsCommand extends Command
{
    use RedisClientTrait;

    public const MAIL_LOGS_AND_CONVERSIONS_IS_RUNNING = 'mail_logs_and_conversions_migration_running';

    public const COMMAND_NAME = "mail:migrate-mail-logs-and-conversions";

    public function __construct(
        private Explorer $database,
        private LogsRepository $logsRepository,
        private LogConversionsRepository $logConversionsRepository,
        private EnvironmentConfig $environmentConfig,
        RedisClientFactory $redisClientFactory,
    ) {
        parent::__construct();

        $this->redisClientFactory = $redisClientFactory;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Migrate mail logs and conversions data to new table.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('STARTING `mail_logs` AND `mail_log_conversions` TABLE DATA MIGRATION');
        $output->writeln('');

        $mailLogsTableName = $this->logsRepository->getTable()->getName();
        $mailLogsV2TableName = $this->logsRepository->getNewTable()->getName();

        $mailLogConversionsTableName = $this->logConversionsRepository->getTable()->getName();
        $mailLogConversionsV2TableName = $this->logConversionsRepository->getNewTable()->getName();

        // Set migration running/start time flag in redis
        $migrationStartTime = new DateTime();
        if ($this->redis()->exists(self::MAIL_LOGS_AND_CONVERSIONS_IS_RUNNING)) {
            $migrationStartTime = new DateTime($this->redis()->get(self::MAIL_LOGS_AND_CONVERSIONS_IS_RUNNING));
        } else {
            $this->redis()->set(self::MAIL_LOGS_AND_CONVERSIONS_IS_RUNNING, $migrationStartTime);
        }

        $this->database->query("
            SET FOREIGN_KEY_CHECKS=0;
            SET UNIQUE_CHECKS=0;
        ");

        // Paging LOOP
        $pageSize = 10000;
        while (true) {
            $lastMigratedId = $this->database
                ->query("SELECT id FROM `{$mailLogsV2TableName}` WHERE created_at <= ? ORDER BY id DESC LIMIT 1", $migrationStartTime)
                ->fetch()
                ?->id ?? 0;

            $maxId = $this->database
                ->query("SELECT id FROM `{$mailLogsTableName}` WHERE created_at <= ? ORDER BY id DESC LIMIT 1", $migrationStartTime)
                ->fetch()
                ?->id ?? 0;

            if ($maxId === 0 || $lastMigratedId === $maxId) {
                break;
            }

            $this->database->query("
                INSERT IGNORE INTO `{$mailLogsV2TableName}` (`id`, `email`, `subject`, `mail_template_id`, `mail_job_id`, `mail_job_batch_id`, `mail_sender_id`, `context`, `delivered_at`, `dropped_at`, `spam_complained_at`, `hard_bounced_at`, `clicked_at`, `opened_at`, `attachment_size`, `created_at`, `updated_at`)
                SELECT `id`, `email`, `subject`, `mail_template_id`, `mail_job_id`, `mail_job_batch_id`, `mail_sender_id`, `context`, `delivered_at`, `dropped_at`, `spam_complained_at`, `hard_bounced_at`, `clicked_at`, `opened_at`, `attachment_size`, `created_at`, `updated_at`
                FROM `{$mailLogsTableName}`
                WHERE id > {$lastMigratedId}
                ORDER BY id ASC
                LIMIT {$pageSize}
            ");

            $this->database->query("
                INSERT IGNORE INTO `{$mailLogConversionsV2TableName}` (`id`, `mail_log_id`, `converted_at`)
                SELECT `{$mailLogConversionsTableName}`.`id`, `mail_log_id`, `converted_at`
                FROM `{$mailLogConversionsTableName}`
                WHERE `mail_log_id` IN (SELECT * FROM (SELECT `id` FROM `{$mailLogsTableName}` WHERE id > {$lastMigratedId} ORDER BY id ASC LIMIT {$pageSize}) as t)
            ");

            $remaining = $maxId-$lastMigratedId;
            $output->write("\r\e[0KMIGRATED IDs: {$lastMigratedId} / {$maxId} (REMAINING: {$remaining})");
        }

        $output->writeln('');
        $output->writeln('DATA MIGRATED');
        $output->writeln('');
        $output->writeln('UPDATING ROWS DIFFERENCES AND INSERTING MISSING ROWS');

        $this->fixTableDifferences(
            $mailLogsTableName,
            $mailLogsV2TableName,
            $mailLogConversionsTableName,
            $mailLogConversionsV2TableName,
            $migrationStartTime
        );

        $output->writeln('');
        $output->writeln('SETUPING AUTO_INCREMENT');

        // Sat AUTO_INCREMENT for new tables to old table values
        $dbName = $this->environmentConfig->get('DB_NAME');
        $this->database->query("
            SELECT MAX(id)+10000 INTO @AutoInc FROM {$mailLogsTableName};

            SET @s:=CONCAT('ALTER TABLE `{$dbName}`.`{$mailLogsV2TableName}` AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            SELECT MAX(id)+10000 INTO @AutoInc FROM {$mailLogConversionsTableName};

            SET @s:=CONCAT('ALTER TABLE `{$dbName}`.`{$mailLogConversionsV2TableName}` AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $output->writeln('');
        $output->writeln('RENAMING TABLES');

        // Rename tables
        $this->database->query("
            ANALYZE TABLE {$mailLogsV2TableName};
            ANALYZE TABLE {$mailLogConversionsV2TableName};
            RENAME TABLE {$mailLogsTableName} TO {$mailLogsTableName}_old,
            {$mailLogsV2TableName} TO {$mailLogsTableName},
            {$mailLogConversionsTableName} TO {$mailLogConversionsTableName}_old,
            {$mailLogConversionsV2TableName} TO {$mailLogConversionsTableName};
        ");

        $output->writeln('');
        $output->writeln('UPDATING ROWS DIFFERENCES AND INSERTING MISSING ROWS');

        $this->fixTableDifferences(
            $mailLogsTableName . '_old',
            $mailLogsTableName,
            $mailLogConversionsTableName . '_old',
            $mailLogConversionsTableName,
            $migrationStartTime
        );

        $this->database->query("
            SET FOREIGN_KEY_CHECKS=1;
            SET UNIQUE_CHECKS=1;
        ");

        // Remove migration running flag in redis
        $this->redis()->del(self::MAIL_LOGS_AND_CONVERSIONS_IS_RUNNING);

        $output->writeln('');
        $output->writeln('DATA MIGRATED SUCCESSFULLY');
        return Command::SUCCESS;
    }

    public function fixTableDifferences(
        string $mailLogsFromTable,
        string $mailLogsToTable,
        string $mailLogConversionsFromTable,
        string $mailLogConversionsToTable,
        DateTime $updatedAfter
    ) {
        $this->database->query("
            UPDATE {$mailLogsToTable} ml_to
            JOIN {$mailLogsFromTable} ml_from on ml_to.id = ml_from.id
            SET ml_to.email = ml_from.email,
                ml_to.updated_at = ml_from.updated_at,
                ml_to.subject = ml_from.subject,
                ml_to.mail_template_id = ml_from.mail_template_id,
                ml_to.mail_job_id = ml_from.mail_job_id,
                ml_to.mail_job_batch_id = ml_from.mail_job_batch_id,
                ml_to.mail_sender_id = ml_from.mail_sender_id,
                ml_to.context = ml_from.context,
                ml_to.delivered_at = ml_from.delivered_at,
                ml_to.dropped_at = ml_from.dropped_at,
                ml_to.spam_complained_at = ml_from.spam_complained_at,
                ml_to.hard_bounced_at = ml_from.hard_bounced_at,
                ml_to.clicked_at = ml_from.clicked_at,
                ml_to.opened_at = ml_from.opened_at,
                ml_to.attachment_size = ml_from.attachment_size
            WHERE ml_from.updated_at > ?
                AND (ml_to.updated_at IS NULL OR ml_from.updated_at != ml_to.updated_at)
        ", $updatedAfter);

        $missingIds = $this->database->query("
            SELECT `id` FROM `{$mailLogsFromTable}`
            WHERE created_at > ?
            AND `id` NOT IN (
                SELECT `id` FROM `{$mailLogsToTable}` WHERE created_at > ?
            )
        ", $updatedAfter, $updatedAfter)->fetchFields();

        if ($missingIds) {
            $this->database->query("
            INSERT IGNORE INTO `{$mailLogsToTable}` (`id`, `email`, `subject`, `mail_template_id`, `mail_job_id`, `mail_job_batch_id`, `mail_sender_id`, `context`, `delivered_at`, `dropped_at`, `spam_complained_at`, `hard_bounced_at`, `clicked_at`, `opened_at`, `attachment_size`, `created_at`, `updated_at`)
            SELECT `id`, `email`, `subject`, `mail_template_id`, `mail_job_id`, `mail_job_batch_id`, `mail_sender_id`, `context`, `delivered_at`, `dropped_at`, `spam_complained_at`, `hard_bounced_at`, `clicked_at`, `opened_at`, `attachment_size`, `created_at`, `updated_at`
            FROM `{$mailLogsFromTable}`
            WHERE `id` IN ?
        ", $missingIds);

            $this->database->query("
            INSERT IGNORE INTO `{$mailLogConversionsToTable}` (`id`, `mail_log_id`, `converted_at`)
            SELECT `id`, `mail_log_id`, `converted_at`
            FROM `{$mailLogConversionsFromTable}`
            WHERE `mail_log_id` IN ?
        ", $missingIds);
        }

        // make sure that any mail_sender_id in the old table is also in the new table
        $this->database->query("
            INSERT INTO `{$mailLogsToTable}` (`email`, `subject`, `mail_template_id`, `mail_job_id`, `mail_job_batch_id`, `mail_sender_id`, `context`, `delivered_at`, `dropped_at`, `spam_complained_at`, `hard_bounced_at`, `clicked_at`, `opened_at`, `attachment_size`, `created_at`, `updated_at`)
            SELECT `email`, `subject`, `mail_template_id`, `mail_job_id`, `mail_job_batch_id`, `mail_sender_id`, `context`, `delivered_at`, `dropped_at`, `spam_complained_at`, `hard_bounced_at`, `clicked_at`, `opened_at`, `attachment_size`, `created_at`, `updated_at`
            FROM {$mailLogsFromTable}
            WHERE `{$mailLogsFromTable}`.created_at >= ?
            AND mail_sender_id NOT IN (
                SELECT `mail_sender_id`
                FROM `{$mailLogsToTable}`
                WHERE `{$mailLogsToTable}`.`created_at` >= ?
            );
        ", $updatedAfter, $updatedAfter);
    }
}
