<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\EnvironmentConfig;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;
use Remp\MailerModule\Repositories\AutoLoginTokensRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateAutologinTokensCommand extends Command
{
    use RedisClientTrait;

    public const AUTOLOGIN_TOKENS_MIGRATION_IS_RUNNING = 'autologin_tokens_migration_running';

    public const COMMAND_NAME = "mail:migrate-autologin-tokens";

    public function __construct(
        private Explorer $database,
        private AutoLoginTokensRepository $autoLoginTokensRepository,
        private EnvironmentConfig $environmentConfig,
        RedisClientFactory $redisClientFactory,
    ) {
        parent::__construct();

        $this->redisClientFactory = $redisClientFactory;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Migrate autologin tokens data to new table.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('STARTING `autologin_tokens` TABLE DATA MIGRATION');
        $output->writeln('');

        $autologinTokensTableName = $this->autoLoginTokensRepository->getTable()->getName();
        $autologinTokensV2TableName = $this->autoLoginTokensRepository->getNewTable()->getName();

        // Set migration running/start time flag in redis
        $migrationStartTime = new DateTime();
        if ($this->redis()->exists(self::AUTOLOGIN_TOKENS_MIGRATION_IS_RUNNING)) {
            $migrationStartTime = new DateTime($this->redis()->get(self::AUTOLOGIN_TOKENS_MIGRATION_IS_RUNNING));
        } else {
            $this->redis()->set(self::AUTOLOGIN_TOKENS_MIGRATION_IS_RUNNING, $migrationStartTime);
        }

        $this->database->query("
            SET FOREIGN_KEY_CHECKS=0;
            SET UNIQUE_CHECKS=0;
        ");

        // Paging LOOP
        $pageSize = 10000;
        while (true) {
            $lastMigratedId = $this->database
                ->query("SELECT id FROM `{$autologinTokensV2TableName}` WHERE created_at <= ? ORDER BY id DESC LIMIT 1", $migrationStartTime)
                ->fetch()
                ?->id ?? 0;

            $maxId = $this->database
                ->query("SELECT id FROM `{$autologinTokensTableName}` WHERE created_at <= ? ORDER BY id DESC LIMIT 1", $migrationStartTime)
                ->fetch()
                ?->id ?? 0;

            if ($maxId === 0 || $lastMigratedId === $maxId) {
                break;
            }

            $this->database->query("
                INSERT IGNORE INTO `{$autologinTokensV2TableName}` (`id`, `token`, `user_id`, `email`, `created_at`, `valid_from`, `valid_to`, `used_count`, `max_count`)
                SELECT `id`, `token`, `user_id`, `email`, `created_at`, `valid_from`, `valid_to`, `used_count`, `max_count`
                FROM `{$autologinTokensTableName}`
                WHERE id > {$lastMigratedId}
                ORDER BY id ASC
                LIMIT {$pageSize}
            ");

            $remaining = $maxId-$lastMigratedId;
            $output->write("\r\e[0KMIGRATED IDs: {$lastMigratedId} / {$maxId} (REMAINING: {$remaining})");
        }

        $output->writeln('');
        $output->writeln('DATA MIGRATED');
        $output->writeln('');
        $output->writeln('UPDATING ROWS DIFFERENCES AND INSERTING MISSING ROWS');

        $this->fixTableDifferences(
            $autologinTokensTableName,
            $autologinTokensV2TableName,
            $migrationStartTime
        );

        $output->writeln('');
        $output->writeln('SETUPING AUTO_INCREMENT');

        // Sat AUTO_INCREMENT for new tables to old table values
        $dbName = $this->environmentConfig->get('DB_NAME');
        $this->database->query("
            SELECT MAX(id)+10000 INTO @AutoInc FROM {$autologinTokensTableName};

            SET @s:=CONCAT('ALTER TABLE `{$dbName}`.`{$autologinTokensV2TableName}` AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $output->writeln('');
        $output->writeln('RENAMING TABLES');

        // Rename tables
        $this->database->query("
            ANALYZE TABLE {$autologinTokensV2TableName};
            RENAME TABLE {$autologinTokensTableName} TO {$autologinTokensTableName}_old,
            {$autologinTokensV2TableName} TO {$autologinTokensTableName};
        ");

        $output->writeln('');
        $output->writeln('UPDATING ROWS DIFFERENCES AND INSERTING MISSING ROWS');

        $this->fixTableDifferences(
            $autologinTokensTableName . '_old',
            $autologinTokensTableName,
            $migrationStartTime
        );

        $this->database->query("
            SET FOREIGN_KEY_CHECKS=1;
            SET UNIQUE_CHECKS=1;
        ");

        // Remove migration running flag in redis
        $this->redis()->del(self::AUTOLOGIN_TOKENS_MIGRATION_IS_RUNNING);

        $output->writeln('');
        $output->writeln('DATA MIGRATED SUCCESSFULLY');
        return Command::SUCCESS;
    }

    public function fixTableDifferences(
        string $fromTable,
        string $toTable,
        DateTime $updatedAfter
    ) {
        $this->database->query("
            UPDATE {$toTable} at_to
            JOIN {$fromTable} at_from on at_to.id = at_from.id
            SET at_to.used_count = at_from.used_count
            WHERE at_to.used_count != at_from.used_count;
        ");

        $missingIds = $this->database->query("
            SELECT `id` FROM `{$fromTable}`
            WHERE created_at > ?
            AND `id` NOT IN (
                SELECT `id` FROM `{$toTable}` WHERE created_at > ?
            )
        ", $updatedAfter, $updatedAfter)->fetchFields();

        if ($missingIds) {
            $this->database->query("
                INSERT IGNORE INTO `{$toTable}` (`id`, `token`, `user_id`, `email`, `created_at`, `valid_from`, `valid_to`, `used_count`, `max_count`)
                SELECT `id`, `token`, `user_id`, `email`, `created_at`, `valid_from`, `valid_to`, `used_count`, `max_count`
                FROM `{$fromTable}`
                WHERE `id` IN ?
            ", $missingIds);
        }
    }
}
